<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\ContactInfo;
use App\Models\SiteSetting;
use App\Support\PageTexts;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        if (!ContactInfo::query()->exists()) {
            ContactInfo::query()->create([
                'name' => 'Команда SelfBio (демо)',
                'email' => 'hello@example.com',
                'socials' => [
                    ['type' => 'Демо-сайт', 'url' => 'https://example.com'],
                ],
            ]);
            Cache::forget('contact.show.data');
        }

        // The heading and the text of the home page are page texts now (the «welcome» table is no longer read).
        if (!SiteSetting::query()->whereIn('key', ['page.contacts.title', 'page.contacts.lead'])->exists()) {
            PageTexts::save([
                'contacts.title' => 'SelfBio: заметки и проекты',
                'contacts.lead' => 'Это демонстрационная главная страница. Здесь можно найти контакты, прочитать записи блога и информацию о проектах.',
            ]);
        }

        if (BlogPost::query()->exists()) {
            return;
        }

        foreach ([
            [
                'title' => 'Добро пожаловать в SelfBio',
                'slug' => 'demo-welcome-to-selfbio',
                'preview' => 'Короткий обзор разделов сайта и того, что здесь можно посмотреть.',
                'body' => '<p>Это демонстрационная запись блога SelfBio. Здесь появляются заметки о проектах, идеях и ходе работы.</p><h2>Что есть на сайте</h2><ul><li>Контакты и блог — на главной странице.</li><li>Обо мне — краткая информация о навыках и опыте.</li></ul>',
                'published_at' => now()->subDays(7),
            ],
            [
                'title' => 'Архитектура веб-приложений',
                'slug' => 'demo-architecture',
                'preview' => 'Заметки о проектировании надежных и масштабируемых сервисов.',
                'body' => '<p>Проектирование архитектуры современных приложений требует внимания к надежности, тестированию и разделению обязанностей.</p>',
                'published_at' => now()->subDays(4),
            ],
        ] as $post) {
            BlogPost::query()->create($post);
        }

        Cache::put('selfbio.blog.version', (string) now()->timestamp);
    }
}
