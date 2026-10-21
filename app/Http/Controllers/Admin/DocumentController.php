<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Document::class);

        $documents = Document::with('product')
            ->when($request->string('type')->value(), fn ($q, $t) => $q->where('type', $t))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.documents.index', compact('documents'));
    }

    public function create()
    {
        $this->authorize('create', Document::class);

        return view('admin.documents.form', ['document' => new Document(['visibility' => 'public', 'language' => 'en']), 'products' => Product::orderBy('sku')->get(['id', 'sku', 'name'])]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Document::class);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:20480'],
            'type' => ['required', Rule::in(array_keys(Document::TYPES))],
            'title.en' => ['required', 'string', 'max:160'], 'title.bn' => ['nullable', 'string', 'max:160'],
            'product_id' => ['nullable', Rule::exists('products', 'id')],
            'visibility' => ['required', Rule::in(['public', 'authenticated', 'restricted'])],
            'language' => ['required', Rule::in(array_keys(config('motogrip.locales')))],
            'version' => ['nullable', 'string', 'max:20'],
        ]);

        $file = $request->file('file');
        $checksum = hash_file('sha256', $file->getRealPath());

        if ($existing = Document::where('checksum', $checksum)->first()) {
            throw ValidationException::withMessages(['file' => 'This exact file is already uploaded as "'.$existing->title.'".']);
        }

        // Random stored name on the private disk; the original name is kept only as metadata.
        $path = $file->store('documents', 'local');

        $document = Document::create([
            'product_id' => $data['product_id'] ?? null, 'type' => $data['type'],
            'title' => array_filter($data['title']), 'disk' => 'local', 'path' => $path,
            'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 200),
            'mime' => 'application/pdf', 'size' => $file->getSize(), 'checksum' => $checksum,
            'visibility' => $data['visibility'], 'language' => $data['language'], 'version' => $data['version'] ?? null,
            'published_at' => $request->boolean('publish_now') ? now() : null,
        ]);

        activity('admin')->causedBy($request->user())->performedOn($document)->withProperties(['visibility' => $document->visibility])->log('document.uploaded');

        return redirect()->route('admin.documents.index')->with('status', 'Document uploaded.');
    }

    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'visibility' => ['required', Rule::in(['public', 'authenticated', 'restricted'])],
            'published' => ['nullable', 'boolean'],
        ]);
        $document->update(['visibility' => $data['visibility'], 'published_at' => $request->boolean('published') ? ($document->published_at ?? now()) : null]);

        activity('admin')->causedBy($request->user())->performedOn($document)->withProperties($data)->log('document.updated');

        return back()->with('status', 'Document updated.');
    }

    public function destroy(Request $request, Document $document)
    {
        $this->authorize('delete', $document);

        activity('admin')->causedBy($request->user())->performedOn($document)->log('document.deleted');
        Storage::disk($document->disk)->delete($document->path);
        $document->forceDelete();

        return redirect()->route('admin.documents.index')->with('status', 'Document deleted.');
    }
}
