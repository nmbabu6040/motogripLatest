<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Navigation;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    /** [group, key, label, type, rules]. Non-secret settings only. Credentials belong in .env. */
    public const FIELDS = [
        'Brand' => [
            'brand_site_name' => ['brand', 'site_name', 'Site name', 'text', ['required', 'string', 'max:80']],
            'brand_tagline' => ['brand', 'tagline', 'Tagline', 'text', ['nullable', 'string', 'max:160']],
        ],
        'Header' => [
            'header_announcement' => ['header', 'announcement', 'Announcement bar text (leave empty to hide)', 'text', ['nullable', 'string', 'max:200']],
            'header_sticky' => ['header', 'sticky', 'Sticky header', 'bool', ['nullable', 'boolean']],
        ],
        'Footer and legal' => [
            'footer_copyright' => ['footer', 'copyright', 'Footer copyright text', 'text', ['nullable', 'string', 'max:200']],
        ],
        'Contact' => [
            'contact_email' => ['contact', 'email', 'Support email (inquiry notifications are sent here)', 'text', ['nullable', 'email', 'max:160']],
            'contact_phone' => ['contact', 'phone', 'Phone', 'text', ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s]+$/']],
            'contact_whatsapp' => ['contact', 'whatsapp', 'WhatsApp number (digits with country code)', 'text', ['nullable', 'regex:/^[0-9]{6,15}$/']],
            'contact_address' => ['contact', 'address', 'Company address', 'text', ['nullable', 'string', 'max:300']],
            'contact_hours' => ['contact', 'business_hours', 'Business hours', 'text', ['nullable', 'string', 'max:200']],
        ],
        'Social profiles' => [
            'social_facebook' => ['social', 'facebook', 'Facebook URL', 'text', ['nullable', 'url', 'max:300']],
            'social_linkedin' => ['social', 'linkedin', 'LinkedIn URL', 'text', ['nullable', 'url', 'max:300']],
            'social_youtube' => ['social', 'youtube', 'YouTube URL', 'text', ['nullable', 'url', 'max:300']],
            'social_instagram' => ['social', 'instagram', 'Instagram URL', 'text', ['nullable', 'url', 'max:300']],
        ],
        'SEO and analytics' => [
            'seo_default_description' => ['seo', 'default_description', 'Default meta description', 'text', ['nullable', 'string', 'max:300']],
            'seo_noindex_site' => ['seo', 'noindex_site', 'Block search engines from the whole site (use on staging only)', 'bool', ['nullable', 'boolean']],
            'integrations_ga_measurement_id' => ['integrations', 'ga_measurement_id', 'Google Analytics measurement ID (G-XXXXXXX)', 'text', ['nullable', 'regex:/^G-[A-Z0-9]{4,20}$/']],
            'integrations_search_console_token' => ['integrations', 'search_console_token', 'Search Console verification token', 'text', ['nullable', 'regex:/^[A-Za-z0-9_\-]{10,100}$/']],
        ],
        'Advertising (Google AdSense)' => [
            'ads_enabled' => ['ads', 'enabled', 'Show advertisements (also requires the AdSense feature flag in the server environment)', 'bool', ['nullable', 'boolean']],
            'ads_publisher_id' => ['ads', 'publisher_id', 'AdSense publisher ID (ca-pub-…) from your approved account', 'text', ['nullable', 'regex:/^ca-pub-[0-9]{8,20}$/']],
            'ads_slot_header' => ['ads', 'slot_header', 'Header ad unit ID', 'text', ['nullable', 'regex:/^[0-9]{6,20}$/']],
            'ads_slot_in_article' => ['ads', 'slot_in_article', 'In-article ad unit ID', 'text', ['nullable', 'regex:/^[0-9]{6,20}$/']],
            'ads_slot_footer' => ['ads', 'slot_footer', 'Footer ad unit ID', 'text', ['nullable', 'regex:/^[0-9]{6,20}$/']],
        ],
    ];

    /** [setting key, label, extensions]. Stored on the public disk under a random name. */
    public const IMAGES = [
        'logo_url' => ['Header logo', 'png,jpg,jpeg,webp'],
        'favicon_url' => ['Favicon', 'png,ico,webp'],
        'hero_image_url' => ['Homepage hero image', 'png,jpg,jpeg,webp'],
        'social_image_url' => ['Default social sharing image', 'png,jpg,jpeg,webp'],
        'default_product_image_url' => ['Default product image', 'png,jpg,jpeg,webp'],
    ];

    public function edit()
    {
        $flat = collect(self::FIELDS)->flatMap(fn ($g) => $g);

        return view('admin.settings.edit', [
            'groups' => self::FIELDS, 'images' => self::IMAGES,
            'values' => $flat->map(fn ($f) => Settings::get($f[0], $f[1], $f[3] === 'bool' ? false : '')),
            'imageValues' => collect(self::IMAGES)->map(fn ($i, $k) => Settings::get('brand', $k)),
            'env' => [
                'Mail driver' => config('mail.default'), 'Queue' => config('queue.default'), 'Timezone' => config('app.timezone'),
                'Locales' => implode(', ', array_keys(config('motogrip.locales'))), 'AdSense feature' => config('motogrip.features.adsense') ? 'on' : 'off',
                'Affiliate module' => config('motogrip.features.affiliate') ? 'on' : 'off', 'Dealer locator' => config('motogrip.features.dealer_locator') ? 'on' : 'off',
            ],
            'maintenance' => app()->isDownForMaintenance(),
        ]);
    }

    public function update(Request $request)
    {
        $flat = collect(self::FIELDS)->flatMap(fn ($g) => $g);
        $rules = $flat->map(fn ($f) => $f[4])->all();
        foreach (self::IMAGES as $key => [$label, $ext]) {
            $rules['img_'.$key] = ['nullable', 'file', 'image', 'mimes:'.$ext, 'max:2048'];
        }
        $data = $request->validate($rules);

        $changes = [];
        foreach ($flat as $name => [$group, $key, , $type]) {
            $old = Settings::get($group, $key, $type === 'bool' ? false : '');
            $new = $type === 'bool' ? $request->boolean($name) : (string) ($data[$name] ?? '');
            if ($old !== $new && (string) $old !== (string) $new) {
                Settings::set($group, $key, $type === 'bool' ? ($new ? '1' : '') : $new, $type === 'bool' ? 'bool' : 'string');
                $changes["{$group}.{$key}"] = ['old' => $old, 'new' => $new];
            }
        }

        foreach (self::IMAGES as $key => $_) {
            if ($file = $request->file('img_'.$key)) {
                $name = 'branding/'.Str::random(32).'.'.$file->extension();
                Storage::disk('public')->putFileAs('', $file, $name);
                if ($oldUrl = Settings::get('brand', $key)) {
                    Storage::disk('public')->delete(Str::after($oldUrl, '/storage/'));
                }
                Settings::set('brand', $key, '/storage/'.$name);
                $changes["brand.{$key}"] = 'uploaded';
            }
        }

        if ($changes) {
            Navigation::forget();
            activity('admin')->causedBy($request->user())->withProperties($changes)->log('settings.updated');
        }

        return back()->with('status', 'Settings saved.');
    }

    public function clearCache(Request $request)
    {
        Cache::flush();
        Artisan::call('view:clear');
        activity('admin')->causedBy($request->user())->log('cache.cleared');

        return back()->with('status', 'Caches cleared.');
    }

    /** Maintenance mode is restricted to super administrators. A bypass link is shown once so you cannot lock yourself out. */
    public function maintenance(Request $request)
    {
        abort_unless($request->user()->hasRole('super-admin'), 403);

        if (app()->isDownForMaintenance()) {
            Artisan::call('up');
            activity('admin')->causedBy($request->user())->log('maintenance.disabled');

            return back()->with('status', 'Maintenance mode is off.');
        }

        $request->validate(['confirm' => ['accepted']]);
        $secret = Str::random(24);
        Artisan::call('down', ['--secret' => $secret, '--retry' => 60]);
        activity('admin')->causedBy($request->user())->log('maintenance.enabled');

        return back()->with('status', 'Maintenance mode is on. Bypass link (save it now): '.url('/'.$secret));
    }
}
