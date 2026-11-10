<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PartnerLinkController extends Controller
{
    public function index()
    {
        $clicks = DB::table('partner_link_clicks')->selectRaw('partner_link_id, SUM(clicks) as c')->groupBy('partner_link_id')->pluck('c', 'partner_link_id');

        return view('admin.partner-links.index', ['links' => PartnerLink::orderBy('sort_order')->orderByDesc('id')->get(), 'clicks' => $clicks]);
    }

    public function create()
    {
        return view('admin.partner-links.form', ['link' => new PartnerLink(['store' => 'amazon', 'is_published' => true])]);
    }

    public function store(Request $request)
    {
        $link = PartnerLink::create($this->data($request, new PartnerLink));
        activity('admin')->causedBy($request->user())->performedOn($link)->log('partner_link.created');
        Cache::forget('partners.exist');

        return redirect()->route('admin.partner-links.index')->with('status', 'Link saved.');
    }

    public function edit(PartnerLink $partnerLink)
    {
        return view('admin.partner-links.form', ['link' => $partnerLink]);
    }

    public function update(Request $request, PartnerLink $partnerLink)
    {
        $partnerLink->update($this->data($request, $partnerLink));
        activity('admin')->causedBy($request->user())->performedOn($partnerLink)->log('partner_link.updated');
        Cache::forget('partners.exist');

        return redirect()->route('admin.partner-links.index')->with('status', 'Link saved.');
    }

    public function destroy(Request $request, PartnerLink $partnerLink)
    {
        $this->deleteImage($partnerLink->image);
        activity('admin')->causedBy($request->user())->performedOn($partnerLink)->log('partner_link.deleted');
        $partnerLink->delete();
        Cache::forget('partners.exist');

        return redirect()->route('admin.partner-links.index')->with('status', 'Link deleted.');
    }

    private function data(Request $request, PartnerLink $link): array
    {
        $d = $request->validate([
            'store' => ['required', Rule::in(array_keys(PartnerLink::STORES))],
            'title.en' => ['required', 'string', 'max:160'], 'title.bn' => ['nullable', 'string', 'max:160'],
            'description.en' => ['nullable', 'string', 'max:500'], 'description.bn' => ['nullable', 'string', 'max:500'],
            'url' => ['required', 'url:https', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        // The link must really belong to the store chosen, so a typo cannot send visitors somewhere else.
        $host = strtolower((string) parse_url($d['url'], PHP_URL_HOST));
        $ok = match ($d['store']) {
            'amazon' => (bool) preg_match('/(^|\.)(amazon\.[a-z.]+|amzn\.to|amzn\.eu|amzn\.asia|a\.co)$/', $host),
            'aliexpress' => (bool) preg_match('/(^|\.)(aliexpress\.[a-z.]+|s\.click\.aliexpress\.com|a\.aliexpress\.com)$/', $host),
            default => true,
        };
        if (! $ok) {
            throw ValidationException::withMessages(['url' => 'This web address does not look like a '.PartnerLink::STORES[$d['store']].' link. Copy the link from your affiliate account.']);
        }

        $image = $link->image;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = 'partners/'.Str::random(32).'.'.$file->extension();
            Storage::disk('public')->putFileAs('', $file, $path);
            $this->deleteImage($image);
            $image = '/storage/'.$path;
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($image);
            $image = null;
        }

        $clean = fn ($a) => array_filter($a ?? [], fn ($v) => $v !== null && $v !== '');

        return [
            'store' => $d['store'], 'title' => $clean($d['title']), 'description' => $clean($d['description'] ?? []), 'url' => $d['url'],
            'image' => $image, 'sort_order' => $d['sort_order'] ?? 0, 'is_published' => $request->boolean('is_published'),
        ];
    }

    private function deleteImage(?string $url): void
    {
        if ($url && str_starts_with($url, '/storage/partners/')) {
            Storage::disk('public')->delete(Str::after($url, '/storage/'));
        }
    }
}
