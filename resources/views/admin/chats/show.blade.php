@php $last = $messages->last()?->id ?? 0; @endphp
<x-layouts.admin-shell title="Chat with {{ $chat->label() }}">
    <div class="mb-3 flex flex-wrap items-center gap-4 text-sm">
        <a class="underline text-industrial" href="{{ route('admin.chats.index') }}">← All chats</a>
        @if ($chat->visitor_email)<span>{{ $chat->visitor_email }}</span>@endif
        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $chat->status === 'open' ? 'bg-green-100 text-green-900' : 'bg-surface' }}">{{ ucfirst($chat->status) }}</span>
        @if ($chat->status === 'open')<form method="POST" action="{{ route('admin.chats.close', $chat) }}">@csrf<button class="underline">Close chat</button></form>
        @else<form method="POST" action="{{ route('admin.chats.reopen', $chat) }}">@csrf<button class="underline">Reopen</button></form>@endif
        <form method="POST" action="{{ route('admin.chats.destroy', $chat) }}" x-on:submit="if (!confirm('Delete this whole conversation?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Delete</button></form>
    </div>

    <div class="max-w-3xl rounded-panel border border-steel bg-white" x-data="{
            last: {{ $last }}, body: '', busy: false, error: '',
            box() { return this.$refs.box; },
            add(m) { const el = document.createElement('div'); el.className = 'flex ' + (m.from === 'staff' ? 'justify-end' : 'justify-start'); const b = document.createElement('div'); b.className = 'max-w-[80%] rounded-2xl px-4 py-2 text-sm ' + (m.from === 'staff' ? 'bg-navy text-white' : 'bg-surface text-ink'); b.textContent = m.body; const t = document.createElement('span'); t.className = 'mt-1 block text-[10px] opacity-60'; t.textContent = (m.by ? m.by + ' · ' : '') + m.at; b.appendChild(t); el.appendChild(b); this.box().appendChild(el); this.last = Math.max(this.last, m.id); this.box().scrollTop = this.box().scrollHeight; },
            async poll() { try { const r = await fetch('{{ route('admin.chats.poll', $chat) }}?after=' + this.last, { headers: { Accept: 'application/json' } }); if (r.ok) (await r.json()).messages.forEach(m => this.add(m)); } catch (e) {} },
            async send() { if (!this.body.trim() || this.busy) return; this.busy = true; this.error = ''; try { const r = await fetch('{{ route('admin.chats.reply', $chat) }}', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ body: this.body }) }); if (!r.ok) throw new Error(); this.body = ''; await this.poll(); } catch (e) { this.error = 'Could not send. Try again.'; } this.busy = false; },
         }" x-init="$refs.box.scrollTop = $refs.box.scrollHeight; setInterval(() => poll(), 5000)">
        <div x-ref="box" class="h-[28rem] space-y-3 overflow-y-auto p-4" aria-live="polite">
            @foreach ($messages as $m)
                <div class="flex {{ $m->sender === 'staff' ? 'justify-end' : 'justify-start' }}"><div class="max-w-[80%] rounded-2xl px-4 py-2 text-sm {{ $m->sender === 'staff' ? 'bg-navy text-white' : 'bg-surface text-ink' }}">{{ $m->body }}<span class="mt-1 block text-[10px] opacity-60">{{ $m->user?->name ? $m->user->name.' · ' : '' }}{{ $m->created_at->format('H:i') }}</span></div></div>
            @endforeach
        </div>
        <form class="flex gap-2 border-t border-steel p-3" x-on:submit.prevent="send()">
            <label class="sr-only" for="reply">Reply</label>
            <input id="reply" x-model="body" maxlength="1000" autocomplete="off" placeholder="Write a reply…" class="grow rounded-control border border-steel px-3 py-2">
            <button class="rounded-control bg-navy px-5 py-2 font-semibold text-white hover:bg-industrial disabled:opacity-60" :disabled="busy">Send</button>
        </form>
        <p x-show="error" x-cloak x-text="error" class="px-4 pb-3 text-sm text-red-800" role="alert"></p>
    </div>
    <p class="mt-3 max-w-3xl text-xs text-ink/70">নতুন বার্তা প্রতি ৫ সেকেন্ডে নিজে আসে। ৯০ দিন কোনো বার্তা না থাকলে চ্যাট নিজে মুছে যায়।</p>
</x-layouts.admin-shell>
