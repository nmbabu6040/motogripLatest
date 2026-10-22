<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        // Global FAQs only (the table can also hold product-specific ones in future).
        return view('admin.faqs.index', ['faqs' => Faq::whereNull('faqable_type')->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        $faq = Faq::create($this->data($request) + ['sort_order' => (int) Faq::whereNull('faqable_type')->max('sort_order') + 1]);
        activity('admin')->causedBy($request->user())->performedOn($faq)->log('faq.created');

        return redirect()->route('admin.faqs.index')->with('status', 'FAQ added.');
    }

    public function edit(Faq $faq)
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($this->data($request) + ['sort_order' => (int) $request->input('sort_order', $faq->sort_order)]);
        activity('admin')->causedBy($request->user())->performedOn($faq)->log('faq.updated');

        return redirect()->route('admin.faqs.index')->with('status', 'FAQ saved.');
    }

    public function destroy(Request $request, Faq $faq)
    {
        activity('admin')->causedBy($request->user())->performedOn($faq)->log('faq.deleted');
        $faq->delete();

        return redirect()->route('admin.faqs.index')->with('status', 'FAQ deleted.');
    }

    private function data(Request $request): array
    {
        $d = $request->validate([
            'question.en' => ['required', 'string', 'max:300'], 'question.bn' => ['nullable', 'string', 'max:300'],
            'answer.en' => ['required', 'string', 'max:3000'], 'answer.bn' => ['nullable', 'string', 'max:3000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $clean = fn ($a) => array_filter($a ?? [], fn ($v) => $v !== null && $v !== '');

        return ['question' => $clean($d['question']), 'answer' => $clean($d['answer']), 'is_published' => $request->boolean('is_published')];
    }
}
