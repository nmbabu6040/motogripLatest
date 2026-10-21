<x-layouts.admin-shell title="Upload document">
    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.documents.store') }}"
        class="max-w-2xl rounded-panel border border-steel bg-white p-6 grid gap-4 sm:grid-cols-2">
        @csrf
        <div class="sm:col-span-2">
            <label class="block text-sm font-semibold mb-1" for="file">PDF file (max 20
                MB)</label>
            <input id="file" type="file" name="file" accept="application/pdf" required>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1" for="t_en">Title (English)</label>
            <input id="t_en" name="title[en]" required value="{{ old('title.en') }}"
                class="w-full rounded-control border border-steel px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1" for="t_bn">Title (বাংলা)</label>
            <input id="t_bn" name="title[bn]" value="{{ old('title.bn') }}"
                class="w-full rounded-control border border-steel px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1" for="type">Type</label>
            <select id="type" name="type" class="w-full rounded-control border border-steel px-3 py-2">
                @foreach (\App\Models\Document::TYPES as $k => $v)
                    <option value="{{ $k }}" @selected(old('type') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1" for="product">Product (for TDS/SDS)</label>
            <select id="product" name="product_id" class="w-full rounded-control border border-steel px-3 py-2">
                <option value="">None</option>
                @foreach ($products as $p)
                    <option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>{{ $p->sku }} ·
                        {{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1" for="vis">Who can download</label>
            <select id="vis" name="visibility" class="w-full rounded-control border border-steel px-3 py-2">
                <option value="public">Anyone</option>
                <option value="authenticated">Signed-in users</option>
                <option value="restricted">Approved dealers and staff only</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1" for="lang">Language</label>
            <select id="lang" name="language" class="w-full rounded-control border border-steel px-3 py-2">
                @foreach (config('motogrip.locales') as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1" for="ver">Version</label>
            <input id="ver" name="version" value="{{ old('version') }}"
                class="w-full rounded-control border border-steel px-3 py-2">
        </div>
        <label class="flex items-center gap-2 self-end">
            <input type="checkbox" name="publish_now" value="1">
            Publish now</label>
        <div class="sm:col-span-2">
            <x-button>Upload</x-button>
            <a href="{{ route('admin.documents.index') }}" class="ml-3 text-industrial underline">Cancel</a>
        </div>
    </form>
</x-layouts.admin-shell>
