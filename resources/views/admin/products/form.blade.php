@php
    $editing = $product->exists;
    $box = 'rounded-panel border border-steel bg-white p-6';
    $input = 'w-full rounded-control border border-steel px-3 py-2';
    $hint = 'mt-1 text-xs text-ink/70';
@endphp
<x-layouts.admin-shell title="{{ $editing ? 'Edit product' : 'Add product' }}">
    <form method="POST" enctype="multipart/form-data"
        action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}"
        class="max-w-3xl space-y-6">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        {{-- 1. Visibility: the most important choice --}}
        <section class="{{ $box }} border-amber">
            <h2 class="text-lg font-extrabold">1. Show on website?</h2>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-semibold mb-1" for="status">Status</label>
                    <select id="status" name="status" class="{{ $input }}">
                        <option value="published" @selected(old('status', $product->status) === 'published')>Visible on website</option>
                        <option value="draft" @selected(old('status', $product->status) === 'draft')>Hidden (draft)</option>
                        <option value="archived" @selected(old('status', $product->status) === 'archived')>Archived</option>
                    </select>
                    <p class="{{ $hint }}">ওয়েবসাইটে দেখাতে "Visible on website" বাছুন।
                        @unless ($canPublish)
                            (আপনার publish করার অনুমতি নেই, তাই draft হিসেবে সেভ হবে।)
                        @endunless
                    </p>
                </div>
                <label class="flex items-start gap-2 self-center">
                    <input type="checkbox" name="is_featured" value="1" class="mt-1"
                        @checked(old('is_featured', $product->is_featured))>
                    <span><strong>Show on home page</strong><br><span class="text-xs">হোমপেজের "Featured" অংশে
                            দেখান</span></span>
                </label>
            </div>
        </section>

        {{-- 2. Basics --}}
        <section class="{{ $box }} grid gap-4 sm:grid-cols-2">
            <h2 class="sm:col-span-2 text-lg font-extrabold">2. Basic information</h2>
            <div>
                <label class="block text-sm font-semibold mb-1" for="cat">Category</label>
                <select id="cat" name="product_category_id" required class="{{ $input }}">
                    <option value="">Choose…</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('product_category_id', $product->product_category_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="sku">SKU (product code)</label>
                <input id="sku" name="sku" required value="{{ old('sku', $product->sku) }}"
                    class="{{ $input }}">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="name_en">Product name (English)</label>
                <input id="name_en" name="name[en]" required
                    value="{{ old('name.en', $product->getTranslation('name', 'en', false)) }}"
                    class="{{ $input }}">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="name_bn">Product name (বাংলা,
                    optional)</label>
                <input id="name_bn" name="name[bn]"
                    value="{{ old('name.bn', $product->getTranslation('name', 'bn', false)) }}"
                    class="{{ $input }}">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="viscosity_grade">Viscosity grade</label>
                <input id="viscosity_grade" name="viscosity_grade" placeholder="e.g. 15W-40"
                    value="{{ old('viscosity_grade', $product->viscosity_grade) }}" class="{{ $input }}">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="api_classification">API classification (only if it
                    applies)</label>
                <input id="api_classification" name="api_classification"
                    value="{{ old('api_classification', $product->api_classification) }}" class="{{ $input }}">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold mb-1" for="application">Used
                    for</label>
                <input id="application" name="application" placeholder="e.g. Diesel engines"
                    value="{{ old('application', $product->application) }}" class="{{ $input }}">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold mb-1" for="sd_en">Short description
                    (English)</label>
                <textarea id="sd_en" name="short_description[en]" rows="2" class="{{ $input }}">{{ old('short_description.en', $product->getTranslation('short_description', 'en', false)) }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold mb-1" for="d_en">Full description
                    (English)</label>
                <textarea id="d_en" name="description[en]" rows="5" class="{{ $input }}">{{ old('description.en', $product->getTranslation('description', 'en', false)) }}</textarea>
            </div>
            <details class="sm:col-span-2">
                <summary class="cursor-pointer text-sm font-semibold">বাংলা বিবরণ যোগ করুন (optional)</summary>
                <div class="mt-3 space-y-3">
                    <textarea name="short_description[bn]" rows="2" aria-label="Short description Bengali"
                        class="{{ $input }}">{{ old('short_description.bn', $product->getTranslation('short_description', 'bn', false)) }}</textarea>
                    <textarea name="description[bn]" rows="5" aria-label="Description Bengali" class="{{ $input }}">{{ old('description.bn', $product->getTranslation('description', 'bn', false)) }}</textarea>
                </div>
            </details>
        </section>

        {{-- 3. Simple lists --}}
        <section class="{{ $box }} grid gap-4 sm:grid-cols-2">
            <h2 class="sm:col-span-2 text-lg font-extrabold">3. Lists <span class="text-sm font-normal">(প্রতি লাইনে
                    একটি করে লিখুন)</span></h2>
            <div>
                <label class="block text-sm font-semibold mb-1" for="features_text">Features</label>
                <textarea id="features_text" name="features_text" rows="5"
                    placeholder="Excellent wear protection&#10;Good thermal stability" class="{{ $input }}">{{ old('features_text', $texts['features']) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="benefits_text">Benefits</label>
                <textarea id="benefits_text" name="benefits_text" rows="5" placeholder="Longer engine life"
                    class="{{ $input }}">{{ old('benefits_text', $texts['benefits']) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="applications_text">Recommended
                    applications</label>
                <textarea id="applications_text" name="applications_text" rows="5"
                    placeholder="Trucks and buses&#10;Generators" class="{{ $input }}">{{ old('applications_text', $texts['applications']) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="packaging_text">Pack sizes</label>
                <textarea id="packaging_text" name="packaging_text" rows="5" placeholder="1 L, 4 L, 20 L, 208 L"
                    class="{{ $input }}">{{ old('packaging_text', $texts['packaging']) }}</textarea>
                <p class="{{ $hint }}">কমা দিয়ে আলাদা করুন।</p>
            </div>
        </section>

        {{-- 4. Technical claims (only for people allowed to manage them) --}}
        @if ($canSpecs)
            <section class="{{ $box }} space-y-6">
                <h2 class="text-lg font-extrabold">4. Technical data</h2>
                <div>
                    <label class="block text-sm font-semibold mb-1" for="specs_text">Specifications</label>
                    <textarea id="specs_text" name="specs_text" rows="6" placeholder="Flash point: 220 °C&#10;Pour point: -33 °C"
                        class="{{ $input }} font-mono text-sm">{{ old('specs_text', $texts['specs']) }}</textarea>
                    <p class="{{ $hint }}">প্রতি লাইনে একটি, এভাবে: <code>নাম: মান</code></p>
                    <label class="mt-2 flex items-start gap-2 text-sm"><input type="checkbox" name="specs_verified"
                            value="1" class="mt-1" @checked(old('specs_verified', $texts['specs_verified']))> <span>এই মানগুলো অনুমোদিত ডেটা
                            শিট বা ল্যাব রিপোর্ট থেকে নেওয়া। <strong>টিক দিলে ওয়েবসাইটে দেখাবে, না দিলে লুকানো
                                থাকবে।</strong></span></label>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1" for="approvals_text">Standards and
                        approvals</label>
                    <textarea id="approvals_text" name="approvals_text" rows="3" placeholder="API CK-4"
                        class="{{ $input }}">{{ old('approvals_text', $texts['approvals']) }}</textarea>
                    <label class="mt-2 flex items-start gap-2 text-sm"><input type="checkbox"
                            name="approvals_verified" value="1" class="mt-1" @checked(old('approvals_verified', $texts['approvals_verified']))>
                        <span>এসবের অফিসিয়াল প্রমাণ আমাদের কাছে আছে। <strong>টিক দিলে ওয়েবসাইটে
                                দেখাবে।</strong></span></label>
                </div>
            </section>
        @endif

        {{-- 5. Images --}}
        <section class="{{ $box }}">
            <h2 class="text-lg font-extrabold">{{ $canSpecs ? '5' : '4' }}. Photos</h2>
            @if ($editing && $product->getMedia('images')->isNotEmpty())
                <ul class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach ($product->getMedia('images') as $m)
                        <li class="text-sm">
                            <img src="{{ $m->hasGeneratedConversion('card') ? $m->getUrl('card') : $m->getUrl() }}"
                                alt="{{ $m->getCustomProperty('alt') }}" width="160" height="160"
                                class="rounded-control border border-steel bg-surface">
                            <label class="mt-1 flex items-center gap-1"><input type="checkbox" name="remove_media[]"
                                    value="{{ $m->id }}"> Remove</label>
                        </li>
                    @endforeach
                </ul>
            @endif
            <label class="mt-3 block text-sm font-semibold mb-1" for="images">Add photos (JPG, PNG or WebP, up to 5
                MB each)</label>
            <input id="images" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
            <label class="mt-3 block text-sm font-semibold mb-1" for="image_alt">Photo description (optional)</label>
            <input id="image_alt" name="image_alt" placeholder="Leave empty to use the product name"
                class="{{ $input }}">
        </section>

        <div class="sticky bottom-0 -mx-4 flex items-center gap-4 border-t border-steel bg-surface px-4 py-3">
            <x-button>Save product</x-button>
            <a href="{{ route('admin.products.index') }}" class="underline text-industrial">Back to list</a>
        </div>
    </form>
</x-layouts.admin-shell>
