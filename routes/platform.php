<?php

declare(strict_types=1);

use App\Orchid\Screens\Account\AccountUserEditScreen;
use App\Orchid\Screens\Account\AccountUserListScreen;
use App\Orchid\Screens\BlogPostEditScreen;
use App\Orchid\Screens\BlogPostListScreen;
use App\Orchid\Screens\CardSettingsScreen;
use App\Orchid\Screens\ContactInfoScreen;
use App\Orchid\Screens\PlatformScreen;
use App\Orchid\Screens\ProfileContentScreen;
use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\SiteSettingsScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

// Main
Route::screen('selfbio/blog/create', BlogPostEditScreen::class)->name('platform.selfbio.blog.create');
Route::screen('selfbio/blog/{post}/edit', BlogPostEditScreen::class)->name('platform.selfbio.blog.edit');
Route::screen('selfbio/blog', BlogPostListScreen::class)->name('platform.selfbio.blog');
Route::screen('selfbio/contacts', ContactInfoScreen::class)->name('platform.selfbio.contacts');
Route::screen('selfbio/profile', ProfileContentScreen::class)->name('platform.selfbio.profile');
Route::screen('selfbio/settings', SiteSettingsScreen::class)->name('platform.selfbio.settings');
Route::screen('selfbio/card-settings', CardSettingsScreen::class)->name('platform.selfbio.card');

// Account users
Route::screen('account/users/{user}/edit', AccountUserEditScreen::class)->name('platform.account.users.edit');
Route::screen('account/users/create', AccountUserEditScreen::class)->name('platform.account.users.create');
Route::screen('account/users', AccountUserListScreen::class)->name('platform.account.users');

Route::screen('/main', PlatformScreen::class)->name('platform.main');

// Platform > Profile
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Profile'), route('platform.profile')));

// Platform > System > Users > User
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(fn (Trail $trail, $user) => $trail
        ->parent('platform.systems.users')
        ->push($user->name, route('platform.systems.users.edit', $user)));

// Platform > System > Users > Create
Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.users')
        ->push(__('Create'), route('platform.systems.users.create')));

// Platform > System > Users
Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Users'), route('platform.systems.users')));

// Platform > System > Roles > Role
Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(fn (Trail $trail, $role) => $trail
        ->parent('platform.systems.roles')
        ->push($role->name, route('platform.systems.roles.edit', $role)));

// Platform > System > Roles > Create
Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.roles')
        ->push(__('Create'), route('platform.systems.roles.create')));

// Platform > System > Roles
Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Roles'), route('platform.systems.roles')));
