<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Support\Seo;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = ['User-agent: *'];
        foreach (Seo::PRIVATE_PATHS as $path) {
            $lines[] = 'Disallow: /'.$path;
        }
        // Scripts, styles and the JSON the pages draw themselves from (/build, /api/blog) stay open to the bots.
        $lines[] = '';
        $lines[] = 'Sitemap: '.url('/sitemap.xml');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        // The map is rebuilt when a post is saved (the blog version changes).
        $xml = Cache::remember('sitemap.xml.'.Cache::get('selfbio.blog.version', '0'), 600, fn (): string => view('sitemap', ['urls' => $this->urls()])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** @return list<array{loc: string, priority: string, lastmod?: string|null}> */
    private function urls(): array
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => url('/about'), 'priority' => '0.7'],
            ['loc' => url('/en'), 'priority' => '0.8'],
            ['loc' => url('/en/about'), 'priority' => '0.6'],
            ['loc' => url('/en/contacts'), 'priority' => '0.4'],
            ['loc' => url('/blog'), 'priority' => '0.8'],
            ['loc' => url('/contacts'), 'priority' => '0.5'],
        ];

        foreach (BlogPost::query()->published()->latest('published_at')->limit(1000)->get(['slug', 'updated_at']) as $post) {
            $urls[] = ['loc' => url('/blog/'.$post->slug), 'lastmod' => $post->updated_at?->toIso8601String(), 'priority' => '0.7'];
        }

        return $urls;
    }
}
