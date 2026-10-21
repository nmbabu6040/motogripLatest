<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentDownloadController extends Controller
{
    public function __invoke(Request $request, Document $document)
    {
        $user = $request->user();
        $isStaff = $user?->can('documents.manage');

        abort_unless($isStaff || ($document->published_at && $document->published_at->isPast()), 404);

        if (! $isStaff && ! $document->canBeDownloadedBy($user)) {
            // Guests are sent to sign in; signed-in users without access get a clear 403.
            return $user ? abort(403) : redirect()->guest(route('login'));
        }

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        // Daily aggregate counter only: no IP, no user id.
        DB::table('document_downloads')->upsert([['document_id' => $document->id, 'downloaded_on' => today()->toDateString(), 'downloads' => 1]], ['document_id', 'downloaded_on'], ['downloads' => DB::raw('downloads + 1')]);

        return Storage::disk($document->disk)->download($document->path, Str::slug($document->title).'.pdf', [
            'Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }
}
