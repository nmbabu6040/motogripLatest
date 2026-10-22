@php $input = 'w-full rounded-control border border-steel px-3 py-2'; $label = 'mb-1 block text-sm font-semibold'; @endphp
<x-layouts.admin-shell title="Edit section: {{ ucfirst(str_replace('_', ' ', $key)) }}">
    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.homepage.update', $key) }}" class="max-w-3xl space-y-6 rounded-panel border border-steel bg-white p-6">
        @csrf @method('PUT')

        @if ($mode === 'data')
            <p class="rounded-control bg-surface p-3 text-sm">এই সেকশনের বিষয়বস্তু অন্য পাতা থেকে আসে।
                @if ($where) <a class="underline text-industrial" href="{{ route($where[1]) }}">{{ $where[0] }} খুলুন →</a>@endif
                এখানে শুধু সেকশনের শিরোনাম বদলানো যায় (খালি রাখলে ডিফল্ট শিরোনাম দেখাবে)।</p>
        @endif
        @if ($mode === 'hero')<p class="rounded-control bg-surface p-3 text-sm">হিরোর ছবি বদলাতে <a class="underline text-industrial" href="{{ route('admin.settings.edit') }}">Settings → Homepage hero image</a> ব্যবহার করুন।</p>@endif
        @if ($mode === 'stats')<p class="rounded-control bg-amber/30 p-3 text-sm font-semibold">শুধু যাচাই করা সংখ্যা দিন। প্রতি লাইনে একটি, এভাবে: <code>Dealers | 120</code> (প্রথমে নাম, তারপর সংখ্যা)।</p>@endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="{{ $label }}" for="t_en">Title (English)</label><input id="t_en" name="title[en]" value="{{ old('title.en', $content['title']['en'] ?? '') }}" class="{{ $input }}"></div>
            <div><label class="{{ $label }}" for="t_bn">Title (বাংলা)</label><input id="t_bn" name="title[bn]" value="{{ old('title.bn', $content['title']['bn'] ?? '') }}" class="{{ $input }}"></div>
        </div>

        @if (in_array($mode, ['text', 'cta', 'hero'], true))
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="{{ $label }}" for="b_en">Text (English)</label><textarea id="b_en" name="body[en]" rows="5" class="{{ $input }}">{{ old('body.en', $content['body']['en'] ?? '') }}</textarea></div>
                <div><label class="{{ $label }}" for="b_bn">Text (বাংলা)</label><textarea id="b_bn" name="body[bn]" rows="5" class="{{ $input }}">{{ old('body.bn', $content['body']['bn'] ?? '') }}</textarea></div>
            </div>
        @endif

        @if (in_array($mode, ['text', 'stats'], true))
            <div>
                <p class="{{ $label }}">{{ $mode === 'stats' ? 'Numbers' : 'Key points (optional)' }}</p>
                <p class="mb-2 text-xs text-ink/70">প্রতি লাইনে একটি। ইংরেজি ও বাংলার লাইন একই ক্রমে লিখুন। @if ($mode === 'text')চাইলে <code>শিরোনাম | ছোট বিবরণ</code> আকারেও লিখতে পারেন।@endif</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <textarea name="points_en" rows="6" aria-label="Points English" placeholder="{{ $mode === 'stats' ? 'Dealers | 120' : 'Designed for heavy-duty engines' }}" class="{{ $input }}">{{ old('points_en', $pointsEn) }}</textarea>
                    <textarea name="points_bn" rows="6" aria-label="Points Bengali" class="{{ $input }}">{{ old('points_bn', $pointsBn) }}</textarea>
                </div>
            </div>
        @endif

        @if ($mode === 'text')
            <div>
                <label class="{{ $label }}" for="image">Photo (optional, JPG/PNG/WebP up to 4 MB)</label>
                @if (! empty($content['image']))
                    <img src="{{ $content['image'] }}" alt="" class="mb-2 h-32 w-auto rounded-control border border-steel">
                    <label class="mb-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_image" value="1"> Remove this photo</label>
                @endif
                <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                <p class="mt-1 text-xs text-ink/70">ছবি দিলে হোমপেজে লেখা বাঁ দিকে, ছবি ডান দিকে দেখাবে। ছবি না দিলে পয়েন্টগুলো ডান দিকে সাজানো তালিকা হিসেবে দেখাবে।</p>
            </div>
        @endif

        <div><x-button>Save section</x-button> <a class="ml-3 underline text-industrial" href="{{ route('admin.homepage.index') }}">Cancel</a></div>
    </form>
</x-layouts.admin-shell>
