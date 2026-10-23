<x-layouts.admin-shell title="Categories and tags">
    <div class="grid gap-6 lg:grid-cols-2 max-w-5xl">
        <section class="rounded-panel border border-steel bg-white p-6"><h2 class="font-extrabold">Categories</h2>
            <form method="POST" action="{{ route('admin.blog.categories.store') }}" class="mt-3 grid gap-2">@csrf<x-field name="name[en]" label="Name (English)" required /><x-field name="name[bn]" label="Name (বাংলা)" /><div><x-button>Add category</x-button></div></form>
            <ul class="mt-4 divide-y divide-steel text-sm">@foreach ($categories as $c)<li class="flex justify-between py-2"><span>{{ $c->name }}</span><form method="POST" action="{{ route('admin.blog.categories.destroy', $c) }}" x-on:submit="if (!confirm('Delete this category?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Delete<span class="sr-only"> {{ $c->name }}</span></button></form></li>@endforeach</ul></section>
        <section class="rounded-panel border border-steel bg-white p-6"><h2 class="font-extrabold">Tags</h2><p class="text-sm">Tags are created when you tag an article.</p>
            <ul class="mt-4 divide-y divide-steel text-sm">@foreach ($tags as $t)<li class="flex justify-between py-2"><span>{{ $t->name }}</span><form method="POST" action="{{ route('admin.blog.tags.destroy', $t) }}" x-on:submit="if (!confirm('Delete this tag?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Delete<span class="sr-only"> {{ $t->name }}</span></button></form></li>@endforeach</ul></section>
    </div>
</x-layouts.admin-shell>
