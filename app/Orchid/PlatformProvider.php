<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);
    }

    /**
     * Register the application menu.
     *
     * @return Menu[]
     */
    public function menu(): array
    {
        $user = auth()->user();
        $canAny = fn (array $permissions): bool => $user?->hasAnyAccess($permissions) ?? false;

        return [
            Menu::make('Настройки сайта')
                ->icon('bs.gear')
                ->route('platform.selfbio.settings')
                ->permission('platform.selfbio.settings.view'),

            Menu::make('Сайт-визитка')
                ->icon('bs.card-text')
                ->canSee($canAny(['platform.selfbio.settings.view', 'platform.selfbio.contacts.view', 'platform.selfbio.blog.view']))
                ->list([
                    Menu::make('Главная и «Обо мне»')->icon('bs.person-vcard')->route('platform.selfbio.profile')->permission('platform.selfbio.settings.view'),
                    Menu::make('Настройки сайта-визитки')->icon('bs.sliders')->route('platform.selfbio.card')->permission('platform.selfbio.settings.view'),
                    Menu::make('Контакты')->icon('bs.person-lines-fill')->route('platform.selfbio.contacts')->permission('platform.selfbio.contacts.view'),
                    Menu::make('Блог')->icon('bs.journal-text')->route('platform.selfbio.blog')->permission('platform.selfbio.blog.view'),
                ]),

            Menu::make('Личный кабинет')
                ->icon('bs.person-workspace')
                ->canSee($canAny(['platform.selfbio.account.view']))
                ->list([
                    Menu::make('Пользователи')->icon('bs.people')->route('platform.account.users')->permission('platform.selfbio.account.view'),
                ]),

            Menu::make('Доступ к админке')
                ->icon('bs.shield-lock')
                ->canSee($canAny(['platform.systems.users', 'platform.systems.roles']))
                ->list([
                    Menu::make(__('Users'))->icon('bs.people')->route('platform.systems.users')->permission('platform.systems.users'),
                    Menu::make(__('Roles'))->icon('bs.shield')->route('platform.systems.roles')->permission('platform.systems.roles'),
                ]),
        ];
    }

    /**
     * Register permissions for the application.
     *
     * @return ItemPermission[]
     */
    public function permissions(): array
    {
        return [
            ItemPermission::group(config('app.name'))
                ->addPermission('platform.selfbio.blog.view', 'Просмотр записей блога')
                ->addPermission('platform.selfbio.blog.edit', 'Редактирование записей блога')
                ->addPermission('platform.selfbio.contacts.view', 'Просмотр контактов')
                ->addPermission('platform.selfbio.contacts.edit', 'Редактирование контактов')
                ->addPermission('platform.selfbio.settings.view', 'Просмотр настроек и текстов страниц')
                ->addPermission('platform.selfbio.settings.edit', 'Изменение названия сайта и текстов страниц'),

            ItemPermission::group('Личный кабинет')
                ->addPermission('platform.selfbio.account.view', 'Просмотр пользователей кабинета')
                ->addPermission('platform.selfbio.account.edit', 'Управление пользователями кабинета'),

            ItemPermission::group(__('System'))
                ->addPermission('platform.systems.roles', __('Roles'))
                ->addPermission('platform.systems.users', __('Users')),
        ];
    }
}
