<x-layouts.admin-shell title="Dealer">
    <form method="POST"
        action="{{ $dealer->exists ? route('admin.dealers.update', $dealer) : route('admin.dealers.store') }}"
        class="max-w-2xl rounded-panel border border-steel bg-white p-6 grid gap-4 sm:grid-cols-2">
        @csrf @if ($dealer->exists)
            @method('PUT')
        @endif
        <x-field name="name" label="Name" :value="$dealer->name" required />
        <x-field name="district" label="District" :value="$dealer->district" required />
        <x-field name="city" label="City" :value="$dealer->city" />
        <x-field name="address" label="Address" :value="$dealer->address" />
        <x-field name="phone" label="Phone" :value="$dealer->phone" />
        <x-field name="email" label="Email" type="email" :value="$dealer->email" />
        <x-field name="latitude" label="Latitude" :value="$dealer->latitude" />
        <x-field name="longitude" label="Longitude" :value="$dealer->longitude" />
        <div class="sm:col-span-2"><x-field name="hours" label="Business hours" :value="$dealer->business_hours['text'] ?? ''" /></div>
        <fieldset class="sm:col-span-2">
            <legend class="text-sm font-semibold mb-1">Public visibility</legend>
            <label class="flex items-center gap-2"><input type="checkbox" name="is_published" value="1"
                    @checked(old('is_published', $dealer->is_published))> Show this dealer in the public locator</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="show_phone" value="1"
                    @checked(old('show_phone', $dealer->show_phone))> Show phone publicly</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="show_email" value="1"
                    @checked(old('show_email', $dealer->show_email))> Show email publicly</label>
        </fieldset>
        <div class="sm:col-span-2"><x-button>Save dealer</x-button> <a class="ml-3 underline text-industrial"
                href="{{ route('admin.dealers.index') }}">Cancel</a></div>
    </form>
</x-layouts.admin-shell>
