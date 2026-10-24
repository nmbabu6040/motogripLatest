<x-layouts.admin-shell title="Affiliates">
    <div class="max-w-3xl rounded-panel border border-steel bg-white p-8">
        <p class="eyebrow">Affiliate module</p>
        <h2 class="mt-1 text-2xl font-extrabold text-navy">The affiliate module is switched off</h2>
        <p class="mt-3">এটা এরর নয়। এই ফিচারটি ইচ্ছা করে বন্ধ রাখা আছে, কারণ চালুর আগে শর্তাবলী, কমিশনের নিয়ম ও পেমেন্টের পদ্ধতি ঠিক করে নিতে হয়।</p>
        <h3 class="mt-6 font-extrabold">চালু করতে</h3>
        <ol class="mt-2 list-decimal space-y-2 pl-6">
            <li>প্রজেক্ট ফোল্ডারের <code>.env</code> ফাইল খুলুন।</li>
            <li>এই লাইনটি যোগ করুন বা বদলান: <code class="rounded bg-surface px-2 py-1">MOTOGRIP_FEATURE_AFFILIATE=true</code></li>
            <li>টার্মিনালে চালান: <code class="rounded bg-surface px-2 py-1">php artisan config:clear</code></li>
            <li>এই পাতাটি আবার খুলুন।</li>
        </ol>
        <p class="mt-6 text-sm">এই সাইট ইনকোয়ারি-ভিত্তিক, তাই অ্যাফিলিয়েটের "সফল রেফারেল" মানে একটি ইনকোয়ারি বা ডিলার আবেদন, অনলাইন বিক্রি নয়।</p>
    </div>
</x-layouts.admin-shell>
