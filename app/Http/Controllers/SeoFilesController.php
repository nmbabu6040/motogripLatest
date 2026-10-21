<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Product;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class SeoFilesController extends Controller
{
    public function robots()
    {
        if (Settings::get('seo', 'noindex_site', false)) {
            return response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain']);
        }

        $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /login', 'Disallow: /user/', 'Disallow: /documents/', 'Disallow: /search', 'Allow: /', '', 'Sitemap: '.url('/sitemap.xml')];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }

    /** Only emitted when AdSense is enabled and a real publisher ID is stored. */
    public function adsTxt()
    {
        $pub = config('motogrip.features.adsense') && Settings::get('ads', 'enabled', false) ? Settings::get('ads', 'publisher_id') : null;
        abort_unless($pub, 404);

        // f08c47fec0942fa0 is Google's published certification authority ID for AdSense.
        return response('google.com, '.str_replace('ca-', '', $pub).', DIRECT, f08c47fec0942fa0'."\n", 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap()
    {
        $xml = Cache::remember('sitemap.xml', 3600, fn () => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private function build(): string
    {
        $entries = [];
        $add = function (string $name, array $params = [], $lastmod = null, bool $hasBn = true) use (&$entries) {
            $en = route($name, $params);
            $bn = $hasBn && Route::has('bn.'.$name) ? route('bn.'.$name, $params) : null;
            $entries[] = ['loc' => $en, 'bn' => $bn, 'lastmod' => $lastmod];
            if ($bn) {
                $entries[] = ['loc' => $bn, 'bn' => $bn, 'en' => $en, 'lastmod' => $lastmod];
            }
        };

        foreach (['home', 'catalog.index', 'resources.index', 'dealers.index', 'blog.index', 'contact.create', 'dealer.apply'] as $route) {
            $add($route);
        }
        foreach (Product::published()->where('noindex', false)->get(['id', 'slug', 'name', 'updated_at']) as $p) {
            $add('catalog.show', ['product' => $p->slug], $p->updated_at, (bool) $p->getTranslation('name', 'bn', false));
        }
        foreach (BlogPost::published()->where('noindex', false)->get(['id', 'slug', 'title', 'updated_at']) as $p) {
            $add('blog.show', ['post' => $p->slug], $p->updated_at, (bool) $p->getTranslation('title', 'bn', false));
        }
        foreach (Page::published()->where('noindex', false)->get(['id', 'slug', 'title', 'updated_at']) as $p) {
            $add('page.show', ['page' => $p->slug], $p->updated_at, (bool) $p->getTranslation('title', 'bn', false));
        }

        $out = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
        foreach ($entries as $e) {
            $en = $e['en'] ?? $e['loc'];
            $out .= '<url><loc>'.e($e['loc']).'</loc>';
            if ($e['lastmod']) {
                $out .= '<lastmod>'.$e['lastmod']->toAtomString().'</lastmod>';
            }
            if ($e['bn']) {
                $out .= '<xhtml:link rel="alternate" hreflang="en" href="'.e($en).'"/><xhtml:link rel="alternate" hreflang="bn" href="'.e($e['bn']).'"/>';
            }
            $out .= '</url>';
        }

        return $out.'</urlset>';
    }
}
