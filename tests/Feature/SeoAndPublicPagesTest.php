<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SeoAndPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_blog_cover_gets_a_working_public_address(): void
    {
        $this->assertSame('/storage/blog/x.png', BlogPost::publicImageUrl('blog/x.png'));
        $this->assertSame('/storage/blog/old.png', BlogPost::publicImageUrl('old.png'));
        $this->assertSame('https://cdn.example.test/a.png', BlogPost::publicImageUrl('https://cdn.example.test/a.png'));
        $this->assertNull(BlogPost::publicImageUrl(null));

        $this->post_('with-cover', ['image' => 'blog/cover.png']);
        $this->getJson('/api/blog')->assertOk()->assertJsonPath('data.0.image_url', '/storage/blog/cover.png');
        $this->getJson('/api/blog/with-cover')->assertOk()->assertJsonPath('image_url', '/storage/blog/cover.png');
    }

    public function test_the_login_page_has_noindex(): void
    {
        $page = $this->get('/login')->assertOk()->getContent();
        $this->assertStringContainsString('noindex', $page);
    }

    public function test_pages_have_a_real_document_with_title_description_and_social_tags(): void
    {
        config(['app.name' => 'Мой дом']);
        $this->post_('hello', ['title' => 'Первая запись', 'preview' => 'Коротко о главном']);

        $html = $this->get('/blog/hello')->assertOk()->getContent();

        $this->assertStringStartsWith('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<title inertia>Первая запись - Мой дом</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Коротко о главном">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/blog/hello').'">', $html);
        $this->assertStringContainsString('property="og:title"', $html);
        $this->assertStringContainsString('"@type":"BlogPosting"', $html);
        $this->assertStringNotContainsString('noindex', $html);
        // A bot that does not run scripts gets the text of the post.
        $this->assertStringContainsString('<noscript>', $html);
        $this->assertStringContainsString('Текст записи', $html);
    }

    public function test_profile_pages_have_distinct_languages_and_search_metadata(): void
    {
        $this->get('/en')->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'en'));
        $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'ru'));

        foreach (['/' => 'ru', '/about' => 'ru', '/contacts' => 'ru', '/en' => 'en', '/en/about' => 'en', '/en/contacts' => 'en'] as $path => $locale) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertStringContainsString('<html lang="'.$locale.'">', $html);
            $this->assertStringContainsString('<link rel="canonical" href="'.url($path).'">', $html);
            $this->assertStringContainsString('hreflang="ru"', $html);
            $this->assertStringContainsString('hreflang="en"', $html);
            $this->assertStringNotContainsString('SoftwareApplication', $html);
        }

        $this->assertStringContainsString('Антон Яценко', $this->get('/')->getContent());
        $this->assertStringContainsString('Anton Yatsenko', $this->get('/en')->getContent());
        $this->assertStringContainsString('"@type":"Person"', $this->get('/about')->getContent());
        $this->assertStringContainsString('"@type":"WebSite"', $this->get('/')->getContent());
        $this->get('/projects')->assertNotFound();
    }

    public function test_home_shows_only_published_recent_articles(): void
    {
        $this->post_('published', ['title' => 'Публичная статья']);
        $this->post_('future', ['title' => 'Будущая статья', 'published_at' => now()->addDay()]);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('Публичная статья', $html);
        $this->assertStringNotContainsString('Будущая статья', $html);
    }

    public function test_the_copy_for_bots_drops_attributes_and_scripts_of_the_post(): void
    {
        $this->post_('risky', ['body' => '<p onclick="steal()">Текст <a href="javascript:evil()">ссылка</a></p><script>alert(1)</script><img src=x onerror=evil()>']);

        $html = $this->get('/blog/risky')->getContent();
        $noscript = substr($html, (int) strpos($html, '<noscript>'));

        $this->assertStringContainsString('<p>Текст ссылка</p>', $noscript);
        $this->assertStringNotContainsString('onclick', $noscript);
        $this->assertStringNotContainsString('javascript:', $noscript);
        $this->assertStringNotContainsString('onerror', $noscript);
    }

    public function test_a_hidden_or_missing_post_is_a_real_404_and_is_not_in_the_map(): void
    {
        $this->post_('future', ['published_at' => now()->addDay()]);

        $this->get('/blog/future')->assertNotFound();
        $this->get('/blog/no-such-post')->assertNotFound();
        $this->assertStringNotContainsString('future', (string) $this->get('/sitemap.xml')->getContent());
    }

    public function test_robots_and_sitemap_hide_the_private_areas_and_show_the_public_ones(): void
    {
        $this->post_('open-post');

        $robots = $this->get('/robots.txt')->assertOk()->getContent();
        foreach (['/admin', '/login', '/account', '/profile'] as $path) {
            $this->assertStringContainsString('Disallow: '.$path, $robots);
        }
        $this->assertStringNotContainsString('Disallow: /api/blog', $robots);
        $this->assertStringNotContainsString('Disallow: /build', $robots);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $robots);

        $sitemap = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $this->assertStringContainsString('<loc>'.url('/blog/open-post').'</loc>', $sitemap);
        $this->assertStringContainsString('<loc>'.url('/about').'</loc>', $sitemap);
        $this->assertStringContainsString('<loc>'.url('/en/about').'</loc>', $sitemap);
        $this->assertStringNotContainsString('/projects', $sitemap);
        $this->assertStringNotContainsString('/admin', $sitemap);
    }

    public function test_private_areas_say_noindex_in_the_header(): void
    {
        $this->get('/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/admin/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/blog')->assertHeaderMissing('X-Robots-Tag');
    }

    /** @param  array<string, mixed>  $extra */
    private function post_(string $slug, array $extra = []): void
    {
        DB::connection('pgsql')->table('blog_posts')->insert([
            'title' => 'Запись', 'slug' => $slug, 'preview' => 'Анонс', 'body' => '<p>Текст записи</p>',
            'published_at' => now()->subHour(), 'created_at' => now(), 'updated_at' => now(), ...$extra,
        ]);
    }
}
