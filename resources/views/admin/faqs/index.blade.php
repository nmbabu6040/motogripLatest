<x-layouts.admin-shell title="FAQs">
    <p class="mb-4 max-w-3xl text-sm">এখানে যোগ করা প্রশ্ন-উত্তর হোমপেজের "FAQs" সেকশনে দেখাবে (সেকশনটি Homepage পাতায় চালু থাকতে হবে)।</p>

    <form method="POST" action="{{ route('admin.faqs.store') }}" class="mb-8 grid max-w-4xl gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-2">
        @csrf
        <h2 class="sm:col-span-2 text-lg font-extrabold">Add a question</h2>
        <div><label class="mb-1 block text-sm font-semibold" for="q_en">Question (English)</label><input id="q_en" name="question[en]" required value="{{ old('question.en') }}" class="w-full rounded-control border border-steel px-3 py-2"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="q_bn">Question (বাংলা, optional)</label><input id="q_bn" name="question[bn]" value="{{ old('question.bn') }}" class="w-full rounded-control border border-steel px-3 py-2"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="a_en">Answer (English)</label><textarea id="a_en" name="answer[en]" rows="4" required class="w-full rounded-control border border-steel px-3 py-2">{{ old('answer.en') }}</textarea></div>
        <div><label class="mb-1 block text-sm font-semibold" for="a_bn">Answer (বাংলা, optional)</label><textarea id="a_bn" name="answer[bn]" rows="4" class="w-full rounded-control border border-steel px-3 py-2">{{ old('answer.bn') }}</textarea></div>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_published" value="1" checked> Show on website</label>
        <div class="sm:col-span-2"><x-button>Add FAQ</x-button></div>
    </form>

    <div class="max-w-4xl overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface"><tr><th class="p-3">Order</th><th class="p-3">Question</th><th class="p-3">Shown</th><th class="p-3"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @forelse ($faqs as $faq)
                <tr class="border-t border-steel">
                    <td class="p-3">{{ $faq->sort_order }}</td><td class="p-3">{{ $faq->question }}</td><td class="p-3">{{ $faq->is_published ? 'Yes' : 'Hidden' }}</td>
                    <td class="flex gap-3 p-3"><a class="text-industrial underline" href="{{ route('admin.faqs.edit', $faq) }}">Edit<span class="sr-only"> {{ $faq->question }}</span></a>
                        <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" x-on:submit="if (!confirm('Delete this FAQ?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Delete<span class="sr-only"> {{ $faq->question }}</span></button></form></td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-6 text-center">No FAQs yet. Add your first question above.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin-shell>
