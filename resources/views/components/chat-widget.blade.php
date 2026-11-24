{{-- Live chat for visitors. Shown only when it is switched on in Settings. The visitor's secret token stays in their own browser. --}}
@if (\App\Support\Settings::get('chat', 'enabled'))
@php
    $cfg = [
        'start' => route('chat.start'), 'send' => route('chat.send'), 'poll' => route('chat.poll'), 'locale' => app()->getLocale(),
        'error' => __('site.chat_error'), 'consentNeeded' => __('site.chat_consent_needed'),
    ];
    $script = <<<'JS'
{
  open: false, token: localStorage.getItem('mg_chat') || '', messages: [], last: 0, text: '', name: '', email: '', consent: false, website: '',
  error: '', busy: false, closed: false, unread: 0, timer: null, cfg: __CFG__,
  hdr() { return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '', 'X-Chat-Token': this.token }; },
  init() {
    if (this.token) { this.poll(); }
    this.$watch('open', (v) => { if (v) { this.unread = 0; this.$nextTick(() => this.down()); } });
    this.tick();
  },
  tick() { clearTimeout(this.timer); this.timer = setTimeout(async () => { if (this.token && !document.hidden) { await this.poll(); } this.tick(); }, this.open ? 4000 : 20000); },
  down() { const b = this.$refs.box; if (b) { b.scrollTop = b.scrollHeight; } },
  add(list) {
    let fresh = 0;
    for (const m of list) { if (m.id > this.last) { this.messages.push(m); this.last = m.id; if (m.from === 'staff') { fresh++; } } }
    if (fresh && !this.open) { this.unread += fresh; }
    this.$nextTick(() => this.down());
  },
  reset() { localStorage.removeItem('mg_chat'); this.token = ''; this.messages = []; this.last = 0; this.closed = false; this.error = ''; },
  async call(url, method, body) {
    const r = await fetch(url, { method: method, headers: this.hdr(), body: body ? JSON.stringify(body) : undefined });
    const j = await r.json().catch(() => ({}));
    if (r.status === 404 && this.token) { this.reset(); }
    if (!r.ok) { throw new Error(j.errors ? Object.values(j.errors)[0][0] : this.cfg.error); }
    return j;
  },
  async poll() { try { const j = await this.call(this.cfg.poll + '?after=' + this.last, 'GET'); this.closed = j.status === 'closed'; this.add(j.messages); } catch (e) { /* try again later */ } },
  async start() {
    this.error = '';
    if (!this.text.trim()) { return; }
    if (!this.consent) { this.error = this.cfg.consentNeeded; return; }
    this.busy = true;
    try {
      const j = await this.call(this.cfg.start, 'POST', { name: this.name, email: this.email, message: this.text, consent: true, website: this.website, locale: this.cfg.locale });
      this.token = j.token; localStorage.setItem('mg_chat', j.token); this.text = ''; this.messages = []; this.last = 0; this.add(j.messages);
    } catch (e) { this.error = e.message; }
    this.busy = false;
  },
  async send() {
    this.error = '';
    if (!this.text.trim() || this.busy) { return; }
    this.busy = true;
    try { const j = await this.call(this.cfg.send, 'POST', { message: this.text, after: this.last }); this.text = ''; this.closed = false; this.add(j.messages); } catch (e) { this.error = e.message; }
    this.busy = false;
  },
}
JS;
    $x = str_replace('__CFG__', json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG), $script);
@endphp
<div x-data="{{ $x }}" x-on:keydown.escape.window="open = false" class="fixed bottom-5 left-5 z-40">
    <button type="button" x-on:click="open = !open" :aria-expanded="open" aria-controls="chat-panel"
            class="relative flex h-14 items-center gap-2 rounded-full bg-navy px-5 font-semibold text-white shadow-lift transition hover:bg-industrial motion-reduce:transition-none">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/></svg>
        <span class="hidden sm:inline">{{ __('site.chat_open') }}</span><span class="sr-only sm:hidden">{{ __('site.chat_open') }}</span>
        <span x-show="unread > 0" x-cloak x-text="unread" class="absolute -right-1 -top-1 flex h-6 min-w-6 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-bold"></span>
    </button>

    <section id="chat-panel" x-show="open" x-cloak x-transition role="dialog" aria-label="{{ __('site.chat_title') }}"
             class="absolute bottom-16 left-0 flex h-[30rem] w-[22rem] max-w-[calc(100vw-2.5rem)] flex-col overflow-hidden rounded-panel bg-white text-ink shadow-lift ring-1 ring-steel">
        <header class="flex items-start justify-between gap-3 bg-navy p-4 text-white">
            <div><h2 class="font-extrabold">{{ __('site.chat_title') }}</h2>@if ($w = \App\Support\Settings::get('chat', 'welcome'))<p class="mt-1 text-xs text-white/80">{{ $w }}</p>@endif</div>
            <button type="button" x-on:click="open = false" class="rounded p-1 hover:bg-white/10" aria-label="{{ __('site.chat_close') }}"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
        </header>

        {{-- Before the first message --}}
        <form x-show="!token" x-on:submit.prevent="start()" class="flex-1 space-y-3 overflow-y-auto p-4 text-sm">
            <div><label class="mb-1 block font-semibold" for="chat-name">{{ __('site.chat_name') }}</label><input id="chat-name" x-model="name" maxlength="120" autocomplete="name" class="w-full rounded-control border border-steel px-3 py-2"></div>
            <div><label class="mb-1 block font-semibold" for="chat-email">{{ __('site.chat_email') }}</label><input id="chat-email" type="email" x-model="email" maxlength="190" autocomplete="email" class="w-full rounded-control border border-steel px-3 py-2"></div>
            <div class="absolute -left-[9999px]" aria-hidden="true"><label>Website<input x-model="website" tabindex="-1" autocomplete="off"></label></div>
            <div><label class="mb-1 block font-semibold" for="chat-text">{{ __('site.chat_message') }}</label><textarea id="chat-text" x-model="text" required maxlength="1000" rows="3" class="w-full rounded-control border border-steel px-3 py-2"></textarea></div>
            <label class="flex items-start gap-2 text-xs"><input type="checkbox" x-model="consent" class="mt-0.5"> <span>{{ __('site.chat_consent') }}</span></label>
            <p x-show="error" x-cloak x-text="error" class="text-sm text-red-700" role="alert"></p>
            <button class="btn btn-primary w-full" :disabled="busy">{{ __('site.chat_start') }}</button>
        </form>

        {{-- The conversation --}}
        <div x-show="token" class="flex min-h-0 flex-1 flex-col">
            <div x-ref="box" class="flex-1 space-y-2 overflow-y-auto p-4" aria-live="polite">
                <template x-for="m in messages" :key="m.id">
                    <div :class="m.from === 'visitor' ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="m.from === 'visitor' ? 'bg-navy text-white' : 'bg-surface'" class="max-w-[80%] rounded-2xl px-3 py-2 text-sm">
                            <span x-text="m.body"></span><span class="mt-1 block text-[10px] opacity-60" x-text="m.at"></span>
                        </div>
                    </div>
                </template>
            </div>
            <p x-show="closed" x-cloak class="px-4 pb-1 text-xs text-ink/70">{{ __('site.chat_closed') }} <button type="button" class="underline" x-on:click="reset()">{{ __('site.chat_new') }}</button></p>
            <p x-show="error" x-cloak x-text="error" class="px-4 pb-1 text-sm text-red-700" role="alert"></p>
            <form x-on:submit.prevent="send()" class="flex gap-2 border-t border-steel p-3">
                <label class="sr-only" for="chat-reply">{{ __('site.chat_message') }}</label>
                <input id="chat-reply" x-model="text" maxlength="1000" autocomplete="off" class="grow rounded-control border border-steel px-3 py-2 text-sm">
                <button class="rounded-control bg-navy px-4 py-2 text-sm font-semibold text-white hover:bg-industrial" :disabled="busy">{{ __('site.chat_send') }}</button>
            </form>
        </div>
    </section>
</div>
@endif
