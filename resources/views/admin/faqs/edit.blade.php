<x-layouts.admin-shell title="Edit FAQ">
    <form method="POST" action="{{ route('admin.faqs.update', $faq) }}" class="grid max-w-4xl gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-2">
        @csrf @method('PUT')
        <div><label class="mb-1 block text-sm font-semibold" for="q_en">Question (English)</label><input id="q_en" name="question[en]" required value="{{ old('question.en', $faq->getTranslation('question', 'en', false)) }}" class="w-full rounded-control border border-steel px-3 py-2"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="q_bn">Question (বাংলা)</label><input id="q_bn" name="question[bn]" value="{{ old('question.bn', $faq->getTranslation('question', 'bn', false)) }}" class="w-full rounded-control border border-steel px-3 py-2"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="a_en">Answer (English)</label><textarea id="a_en" name="answer[en]" rows="5" required class="w-full rounded-control border border-steel px-3 py-2">{{ old('answer.en', $faq->getTranslation('answer', 'en', false)) }}</textarea></div>
        <div><label class="mb-1 block text-sm font-semibold" for="a_bn">Answer (বাংলা)</label><textarea id="a_bn" name="answer[bn]" rows="5" class="w-full rounded-control border border-steel px-3 py-2">{{ old('answer.bn', $faq->getTranslation('answer', 'bn', false)) }}</textarea></div>
        <div><label class="mb-1 block text-sm font-semibold" for="sort">Order (small number shows first)</label><input id="sort" type="number" min="0" name="sort_order" value="{{ old('sort_order', $faq->sort_order) }}" class="w-full rounded-control border border-steel px-3 py-2"></div>
        <label class="flex items-center gap-2 self-end"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $faq->is_published))> Show on website</label>
        <div class="sm:col-span-2"><x-button>Save FAQ</x-button> <a class="ml-3 text-industrial underline" href="{{ route('admin.faqs.index') }}">Cancel</a></div>
    </form>
</x-layouts.admin-shell>
