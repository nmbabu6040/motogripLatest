<x-layouts.admin-shell title="Comments">
    <nav class="mb-4 flex flex-wrap gap-2 text-sm" aria-label="Filter">@foreach (['pending' => 'Awaiting review', 'reported' => 'Reported', 'approved' => 'Approved', 'spam' => 'Spam', 'rejected' => 'Rejected'] as $k => $v)<a href="?filter={{ $k }}" class="rounded-control border border-steel px-3 py-1.5 {{ $filter === $k ? 'bg-navy text-white' : 'bg-white' }}">{{ $v }}</a>@endforeach</nav>
    <ul class="space-y-3 max-w-4xl">
        @forelse ($comments as $c)
            <li class="rounded-panel border border-steel bg-white p-4 text-sm">
                <p class="font-semibold">{{ $c->authorName() }} <span class="font-normal text-xs">on {{ $c->post?->title }} · {{ $c->created_at->format('Y-m-d H:i') }} · {{ ucfirst($c->status) }}@if ($c->reports_count) · {{ $c->reports_count }} report(s)@endif</span></p>
                <p class="mt-1 whitespace-pre-line">{{ $c->body }}</p>
                <div class="mt-3 flex flex-wrap gap-3">
                    @foreach (['approved' => 'Approve', 'rejected' => 'Reject', 'spam' => 'Mark spam'] as $st => $label)@if ($c->status !== $st)<form method="POST" action="{{ route('admin.comments.update', $c) }}">@csrf @method('PUT')<input type="hidden" name="status" value="{{ $st }}"><button class="underline text-industrial">{{ $label }}</button></form>@endif @endforeach
                    <form method="POST" action="{{ route('admin.comments.destroy', $c) }}" x-on:submit="if (!confirm('Delete this comment?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Delete</button></form>
                </div>
            </li>
        @empty
            <li class="rounded-panel bg-white p-6 text-center">Nothing here.</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $comments->links() }}</div>
</x-layouts.admin-shell>
