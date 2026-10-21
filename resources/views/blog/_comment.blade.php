<li id="comment-{{ $comment->id }}"
    class="rounded-control border border-steel p-4 {{ $depth ? 'ml-6 bg-surface' : 'bg-white' }}">
    <p class="text-sm font-semibold">{{ $comment->authorName() }} <span class="font-normal text-xs">·
            {{ $comment->created_at->format('Y-m-d H:i') }}</span></p>
    <p class="mt-1 whitespace-pre-line">{{ $comment->body }}</p>
    <div class="mt-2 flex flex-wrap gap-4 text-sm" x-data="{ reply: false, report: false }">
        <button type="button" class="underline text-industrial"
            x-on:click="reply = !reply; report = false">{{ __('site.reply') }}</button>
        <button type="button" class="underline"
            x-on:click="report = !report; reply = false">{{ __('site.report') }}</button>
        <form x-show="reply" x-cloak method="POST" action="{{ $lr('blog.comment', ['post' => $post]) }}"
            class="w-full">
            @csrf
            <input type="hidden" name="parent_id" value="{{ $comment->id }}">
            @guest
                <div class="grid gap-2 sm:grid-cols-2">
                    <input name="guest_name" required placeholder="{{ __('site.name') }}"
                        aria-label="{{ __('site.name') }}" class="rounded-control border border-steel px-3 py-2">
                    <input type="email" name="guest_email" required placeholder="{{ __('site.email') }}"
                        aria-label="{{ __('site.email') }}" class="rounded-control border border-steel px-3 py-2">
                </div>
            @endguest
            <div class="hidden" aria-hidden="true">
                <input name="website" tabindex="-1" autocomplete="off">
            </div>
            <label class="sr-only" for="r{{ $comment->id }}">{{ __('site.reply') }}</label>
            <textarea id="r{{ $comment->id }}" name="body" rows="3" required
                class="mt-2 w-full rounded-control border border-steel px-3 py-2"></textarea>
            <x-button class="mt-2">{{ __('site.submit') }}</x-button>
        </form>
        <form x-show="report" x-cloak method="POST" action="{{ $lr('blog.comment.report', ['comment' => $comment]) }}"
            class="w-full">
            @csrf
            <label class="sr-only" for="rp{{ $comment->id }}">{{ __('site.reason') }}</label>
            <input id="rp{{ $comment->id }}" name="reason" required maxlength="255"
                placeholder="{{ __('site.reason') }}" class="w-full rounded-control border border-steel px-3 py-2">
            <x-button class="mt-2">{{ __('site.report') }}</x-button>
        </form>
    </div>
    @if (!$depth && $comment->replies->isNotEmpty())
        <ul class="mt-3 space-y-3">
            @foreach ($comment->replies as $reply)
                @include('blog._comment', ['comment' => $reply, 'depth' => 1, 'post' => $post])
            @endforeach
        </ul>
    @endif
</li>
