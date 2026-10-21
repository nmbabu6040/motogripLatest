<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()->with('category')
            ->when($request->string('q')->trim()->value(), fn ($q, $t) => $q->where(fn ($w) => $w->where('sku', 'like', "%{$t}%")->orWhere('name', 'like', "%{$t}%")))
            ->when($request->string('status')->value(), fn ($q, $s) => $q->where('status', $s))
            ->latest('updated_at')->paginate(20)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Product::class);

        // New products start as "published" for people allowed to publish, so they show on the website straight away.
        $status = $request->user()->can('products.publish') ? 'published' : 'draft';

        return view('admin.products.form', $this->formData(new Product(['status' => $status])));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        $product = DB::transaction(function () use ($request) {
            $product = new Product;
            $this->save($request, $product);
            return $product;
        });

        activity('admin')->causedBy($request->user())->performedOn($product)->log('product.created');

        return redirect()->route('admin.products.index')->with('status', 'Product saved'.($product->status === 'published' ? ' and visible on the website.' : ' as a draft (hidden from visitors).'));
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        return view('admin.products.form', $this->formData($product));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        DB::transaction(fn () => $this->save($request, $product));

        activity('admin')->causedBy($request->user())->performedOn($product)->log('product.updated');

        return redirect()->route('admin.products.index')->with('status', 'Product saved'.($product->status === 'published' ? ' and visible on the website.' : ' as a draft (hidden from visitors).'));
    }

    public function destroy(Request $request, Product $product)
    {
        $this->authorize('delete', $product);

        activity('admin')->causedBy($request->user())->performedOn($product)->log('product.deleted');
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product moved to trash.');
    }

    private function save(Request $request, Product $product): void
    {
        $user = $request->user();

        $d = $request->validate([
            'product_category_id' => ['required', Rule::exists('product_categories', 'id')],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($product->id)],
            'name.en' => ['required', 'string', 'max:160'], 'name.bn' => ['nullable', 'string', 'max:160'],
            'short_description.en' => ['nullable', 'string', 'max:400'], 'short_description.bn' => ['nullable', 'string', 'max:400'],
            'description.en' => ['nullable', 'string', 'max:10000'], 'description.bn' => ['nullable', 'string', 'max:10000'],
            'application' => ['nullable', 'string', 'max:120'],
            'viscosity_grade' => ['nullable', 'string', 'max:40'],
            'api_classification' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'features_text' => ['nullable', 'string', 'max:5000'],
            'benefits_text' => ['nullable', 'string', 'max:5000'],
            'applications_text' => ['nullable', 'string', 'max:5000'],
            'packaging_text' => ['nullable', 'string', 'max:1000'],
            'specs_text' => ['nullable', 'string', 'max:6000'],
            'approvals_text' => ['nullable', 'string', 'max:3000'],
            'images' => ['array', 'max:10'], 'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:160'],
            'remove_media' => ['array'], 'remove_media.*' => ['integer'],
        ]);

        if ($d['status'] === 'published' && $product->status !== 'published' && ! $user->can('products.publish')) {
            throw ValidationException::withMessages(['status' => 'You do not have permission to publish products. Save it as a draft instead.']);
        }

        $clean = fn (array $a) => array_filter($a, fn ($v) => $v !== null && $v !== '');
        $published = $d['status'] === 'published';

        $product->fill([
            'product_category_id' => $d['product_category_id'],
            'sku' => $d['sku'],
            'slug' => $product->slug ?: $this->uniqueSlug($d['name']['en'], $product),
            'name' => $clean($d['name']),
            'short_description' => $clean($d['short_description'] ?? []),
            'description' => $clean($d['description'] ?? []),
            'application' => $d['application'] ?? null,
            'viscosity_grade' => $d['viscosity_grade'] ?? null,
            'api_classification' => $d['api_classification'] ?? null,
            'status' => $d['status'],
            'is_featured' => $request->boolean('is_featured'),
            // A published product is visible immediately (never left with a future date).
            'published_at' => $published ? ($product->published_at && $product->published_at->isPast() ? $product->published_at : now()) : null,
        ])->save();

        // Features, benefits and applications: one per line.
        $product->features()->delete();
        $order = 0;
        foreach (['feature' => 'features_text', 'benefit' => 'benefits_text', 'application' => 'applications_text'] as $kind => $field) {
            foreach ($this->lines($d[$field] ?? '') as $line) {
                $product->features()->create(['kind' => $kind, 'text' => ['en' => $line], 'sort_order' => $order++]);
            }
        }

        // Pack sizes: comma separated, e.g. "1 L, 4 L, 20 L, 208 L".
        $product->packaging()->delete();
        $packs = array_values(array_filter(array_map('trim', preg_split('/[,\n\r]+/', (string) ($d['packaging_text'] ?? '')))));
        foreach ($packs as $i => $label) {
            [$value, $unit] = $this->parsePack($label);
            $product->packaging()->create(['label' => mb_substr($label, 0, 60), 'size_value' => $value, 'size_unit' => $unit, 'sort_order' => $i, 'is_active' => true]);
        }

        // Specifications and standards are claims about the product, so only users with specifications.manage can change them.
        if ($user->can('specifications.manage')) {
            $specsVerified = $request->boolean('specs_verified');
            $product->specifications()->delete();
            foreach ($this->lines($d['specs_text'] ?? '') as $i => $line) {
                $parts = preg_split('/\s*[:=|]\s*/u', $line, 2);
                if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
                    throw ValidationException::withMessages(['specs_text' => "This line needs a name and a value, like \"Flash point: 220 °C\": {$line}"]);
                }
                $product->specifications()->create(['name' => ['en' => mb_substr($parts[0], 0, 120)], 'value' => mb_substr($parts[1], 0, 120), 'is_typical' => true, 'is_verified' => $specsVerified, 'sort_order' => $i]);
            }

            $approvalsVerified = $request->boolean('approvals_verified');
            $product->approvals()->delete();
            foreach ($this->lines($d['approvals_text'] ?? '') as $line) {
                $upper = Str::upper($line);
                $type = str_starts_with($upper, 'API') ? 'api' : (str_starts_with($upper, 'ACEA') ? 'acea' : 'other');
                $product->approvals()->create(['type' => $type, 'name' => mb_substr($line, 0, 120), 'verified_at' => $approvalsVerified ? now() : null]);
            }
        }

        foreach ($d['remove_media'] ?? [] as $id) {
            $product->media()->whereKey($id)->first()?->delete();
        }
        foreach ($request->file('images', []) as $file) {
            $product->addMedia($file)->usingFileName(Str::random(24).'.'.$file->extension())
                ->withCustomProperties(['alt' => $d['image_alt'] ?: $d['name']['en']])->toMediaCollection('images');
        }
    }

    /** @return string[] */
    private function lines(?string $text): array
    {
        return collect(preg_split('/\R/u', (string) $text))
            ->map(fn ($l) => trim(preg_replace('/^[\s\-\*•]+/u', '', $l)))
            ->filter()->unique()->take(60)->values()->all();
    }

    /** "4 L" => [4.0, 'l'], "200 ml" => [200.0, 'ml'] */
    private function parsePack(string $label): array
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*(ml|ltr|litre|liter|l|kg|g)?/iu', $label, $m)) {
            $unit = strtolower($m[2] ?? 'l');

            return [(float) $m[1], in_array($unit, ['ltr', 'litre', 'liter'], true) ? 'l' : $unit];
        }

        return [0.0, 'l'];
    }

    private function uniqueSlug(string $name, Product $product): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        for ($i = 2; Product::withTrashed()->where('slug', $slug)->where('id', '!=', $product->id ?? 0)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }
        return $slug;
    }

    private function formData(Product $product): array
    {
        $product->loadMissing(['features', 'packaging', 'specifications', 'approvals', 'media']);

        $texts = [
            'features' => $product->features->where('kind', 'feature')->map(fn ($f) => $f->text)->join("\n"),
            'benefits' => $product->features->where('kind', 'benefit')->map(fn ($f) => $f->text)->join("\n"),
            'applications' => $product->features->where('kind', 'application')->map(fn ($f) => $f->text)->join("\n"),
            'packaging' => $product->packaging->pluck('label')->join(', '),
            'specs' => $product->specifications->map(fn ($s) => $s->name.': '.trim($s->value.' '.$s->unit))->join("\n"),
            'approvals' => $product->approvals->pluck('name')->join("\n"),
            'specs_verified' => $product->specifications->isNotEmpty() && $product->specifications->every(fn ($s) => $s->is_verified),
            'approvals_verified' => $product->approvals->isNotEmpty() && $product->approvals->every(fn ($a) => $a->verified_at),
        ];

        return [
            'product' => $product,
            'categories' => ProductCategory::orderBy('sort_order')->get(),
            'texts' => $texts,
            'canSpecs' => auth()->user()->can('specifications.manage'),
            'canPublish' => auth()->user()->can('products.publish'),
        ];
    }
}
