<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $frontend = rtrim((string) config('custom.urls.frontend_url', config('app.url')), '/');
        $staticPaths = [
            ['loc' => '/', 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => '/tours', 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => '/about', 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => '/why-us', 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => '/experiences', 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => '/stories', 'changefreq' => 'weekly', 'priority' => '0.6'],
            ['loc' => '/contact', 'changefreq' => 'monthly', 'priority' => '0.8'],
        ];

        $tours = Tour::query()
            ->published()
            ->orderByDesc('updated_at')
            ->get(['tour_slug', 'updated_at']);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($staticPaths as $path) {
            $xml .= $this->urlNode($frontend . $path['loc'], null, $path['changefreq'], $path['priority']);
        }

        foreach ($tours as $tour) {
            $xml .= $this->urlNode(
                $frontend . '/tours/' . rawurlencode($tour->tour_slug),
                $tour->updated_at,
                'weekly',
                '0.8'
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    protected function urlNode(string $loc, $lastmod, string $changefreq, string $priority): string
    {
        $node = "  <url>\n    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
        if ($lastmod) {
            $node .= '    <lastmod>' . $lastmod->toAtomString() . "</lastmod>\n";
        }
        $node .= "    <changefreq>{$changefreq}</changefreq>\n";
        $node .= "    <priority>{$priority}</priority>\n";
        $node .= "  </url>\n";

        return $node;
    }
}
