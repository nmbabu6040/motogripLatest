<?php

use App\Models\Faq;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function editor(string $role = 'content-manager'): User
{
    return User::factory()->create()->assignRole($role);
}

it('lets a content manager add FAQs that then appear on the home page', function () {
    $this->actingAs(editor())->post('/admin/faqs', ['question' => ['en' => 'What does 15W-40 mean?'], 'answer' => ['en' => 'It is a multigrade viscosity grade.'], 'is_published' => '1'])->assertRedirect();

    expect(Faq::count())->toBe(1);
    $this->get('/')->assertSee('What does 15W-40 mean?')->assertSee('It is a multigrade viscosity grade.');
});

it('hides unpublished FAQs from visitors', function () {
    $this->actingAs(editor())->post('/admin/faqs', ['question' => ['en' => 'Secret question'], 'answer' => ['en' => 'Secret answer']]);

    $this->get('/')->assertDontSee('Secret question');
});

it('validates FAQs and blocks people without homepage permission', function () {
    $this->actingAs(editor())->post('/admin/faqs', ['question' => ['en' => ''], 'answer' => ['en' => '']])->assertSessionHasErrors(['question.en', 'answer.en']);
    $this->actingAs(editor('sales-manager'))->get('/admin/faqs')->assertForbidden();
    $this->actingAs(editor('sales-manager'))->post('/admin/faqs', ['question' => ['en' => 'x'], 'answer' => ['en' => 'y']])->assertForbidden();
});

it('edits and deletes FAQs', function () {
    $user = editor();
    $faq = Faq::create(['question' => ['en' => 'Old question'], 'answer' => ['en' => 'Old answer'], 'is_published' => true, 'sort_order' => 1]);

    $this->actingAs($user)->put("/admin/faqs/{$faq->id}", ['question' => ['en' => 'New question'], 'answer' => ['en' => 'New answer'], 'is_published' => '1', 'sort_order' => 3])->assertRedirect();
    expect($faq->fresh()->getTranslation('question', 'en'))->toBe('New question')->and($faq->fresh()->sort_order)->toBe(3);

    $this->actingAs($user)->delete("/admin/faqs/{$faq->id}")->assertRedirect();
    expect(Faq::count())->toBe(0);
});

it('saves a homepage section with key points and shows them next to the text', function () {
    $this->actingAs(editor())->put('/admin/homepage/why_choose', [
        'title' => ['en' => 'Why choose Motogrip'], 'body' => ['en' => 'We keep things simple.'],
        'points_en' => "Quality base oils\nTechnical support", 'points_bn' => "মানসম্পন্ন বেস অয়েল\nটেকনিক্যাল সাপোর্ট",
    ])->assertRedirect(route('admin.homepage.index'));

    $this->get('/')->assertSee('Why choose Motogrip')->assertSee('Quality base oils')->assertSee('Technical support');
    $this->get('/bn')->assertSee('মানসম্পন্ন বেস অয়েল');
});

it('loads saved points back into the editor', function () {
    $user = editor();
    $this->actingAs($user)->put('/admin/homepage/benefits', ['title' => ['en' => 'Benefits'], 'points_en' => "Longer engine life | Fewer breakdowns\nBetter protection"]);

    $this->get('/admin/homepage/benefits/edit')->assertOk()->assertSee('Longer engine life | Fewer breakdowns')->assertSee('Better protection');
});

it('uploads, replaces and removes a section photo', function () {
    Storage::fake('public');
    $user = editor();

    $this->actingAs($user)->put('/admin/homepage/industries', ['title' => ['en' => 'Industries'], 'image' => UploadedFile::fake()->image('one.jpg', 800, 500)])->assertRedirect();
    $first = json_decode(DB::table('homepage_sections')->where('key', 'industries')->value('content'), true)['image'];
    expect($first)->toStartWith('/storage/homepage/');
    Storage::disk('public')->assertExists(Str::after($first, '/storage/'));
    $this->get('/')->assertSee($first);

    $this->actingAs($user)->put('/admin/homepage/industries', ['title' => ['en' => 'Industries'], 'image' => UploadedFile::fake()->image('two.png', 800, 500)]);
    $second = json_decode(DB::table('homepage_sections')->where('key', 'industries')->value('content'), true)['image'];
    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing(Str::after($first, '/storage/'));

    $this->actingAs($user)->put('/admin/homepage/industries', ['title' => ['en' => 'Industries'], 'remove_image' => '1']);
    expect(json_decode(DB::table('homepage_sections')->where('key', 'industries')->value('content'), true)['image'])->toBeNull();
    Storage::disk('public')->assertMissing(Str::after($second, '/storage/'));
});

it('rejects non-image uploads for a section photo', function () {
    Storage::fake('public');

    $this->actingAs(editor())->put('/admin/homepage/industries', ['title' => ['en' => 'X'], 'image' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php')])->assertSessionHasErrors('image');
});

it('points the editor to the page where each data section is managed', function () {
    $this->actingAs(editor())->get('/admin/homepage')->assertOk()->assertSee('FAQs')->assertSee('Documents')->assertSee('Products')->assertSee('about-us');
});

it('keeps unknown homepage sections out of reach', function () {
    $this->actingAs(editor())->get('/admin/homepage/not_a_section/edit')->assertNotFound();
    $this->actingAs(editor())->put('/admin/homepage/not_a_section', ['title' => ['en' => 'x']])->assertNotFound();
});
