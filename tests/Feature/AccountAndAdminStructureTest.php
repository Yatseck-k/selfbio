<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User as AccountUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountAndAdminStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_account_requires_authentication(): void
    {
        $this->get('/account')->assertRedirect('/login');

        $user = AccountUser::factory()->create();
        $this->actingAs($user);
        $this->get('/account')->assertRedirect('/');
    }

    public function test_the_admin_menu_is_grouped_by_the_parts_of_the_site(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $html = $this->get('/admin/main')->assertOk()->getContent();

        foreach (['Настройки сайта', 'Сайт-визитка', 'Личный кабинет', 'Доступ к админке'] as $label) {
            $this->assertStringContainsString($label, $html, $label);
        }
        $this->assertStringNotContainsString('Главная страница', $html);
        $this->assertStringNotContainsString('Приветствие', $html);
        $this->assertStringNotContainsString('/admin/selfbio/welcome', $html);
    }

    public function test_the_admin_menu_groups_open_and_close_and_the_main_page_is_short(): void
    {
        DB::connection('pgsql')->table('blog_posts')->insert(['title' => 'Т', 'slug' => 'dash', 'preview' => 'А', 'body' => 'Т', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->admin(), 'admin');

        $html = $this->get('/admin/main')->assertOk()->getContent();

        // The groups of the menu are lists that fold: their items are inside a collapse.
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'data-bs-toggle="collapse"'));
        $this->assertStringContainsString('1 запись в блоге', $html);
    }

    public function test_the_main_page_shows_only_the_cards_the_person_may_use(): void
    {
        $this->actingAs($this->admin(['platform.index' => true, 'platform.selfbio.blog.view' => true]), 'admin');

        $html = $this->get('/admin/main')->assertOk()->getContent();

        $this->assertStringContainsString('Сайт-визитка', $html);
        $this->assertStringNotContainsString('Личный кабинет</h5>', $html);
    }

    public function test_account_users_are_managed_in_the_admin_panel(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/account/users')->assertOk();
        $this->get('/admin/account/users/create')->assertOk();
        $this->post('/admin/account/users/create/save', ['user' => ['name' => 'Анна', 'email' => 'anna@example.test', 'password' => 'short']])
            ->assertSessionHasErrors('user.password');
        $this->post('/admin/account/users/create/save', ['user' => ['name' => 'Анна', 'email' => 'anna@example.test', 'password' => 'a-long-password-1']])->assertRedirect();

        $anna = AccountUser::query()->where('email', 'anna@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('a-long-password-1', $anna->password));
        $this->get('/admin/account/users')->assertOk()->assertSee('anna@example.test');

        // An empty password keeps the old one; the email stays unique.
        $this->post("/admin/account/users/{$anna->id}/edit/save", ['user' => ['name' => 'Анна К.', 'email' => 'anna@example.test', 'password' => '']])->assertRedirect();
        $this->assertTrue(Hash::check('a-long-password-1', $anna->refresh()->password));
        $this->assertSame('Анна К.', $anna->name);
        AccountUser::factory()->create(['email' => 'taken@example.test']);
        $this->post("/admin/account/users/{$anna->id}/edit/save", ['user' => ['name' => 'Анна', 'email' => 'taken@example.test', 'password' => '']])
            ->assertSessionHasErrors('user.email');

        $this->post('/admin/account/users/remove', ['user' => $anna->id])->assertRedirect('/admin/account/users');
        $this->assertDatabaseMissing('users', ['email' => 'anna@example.test'], 'pgsql');
    }

    public function test_a_user_made_in_the_admin_panel_signs_in_to_the_account(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->post('/admin/account/users/create/save', ['user' => ['name' => 'Борис', 'email' => 'boris@example.test', 'password' => 'a-long-password-2']])->assertRedirect();
        auth('admin')->logout();
        auth()->shouldUse('web');

        $this->post('/login', ['email' => 'boris@example.test', 'password' => 'a-long-password-2'])->assertRedirect('/account');
        $this->assertAuthenticated('web');
    }

    public function test_only_an_admin_with_the_account_permission_sees_the_account_screens(): void
    {
        $this->actingAs($this->admin(['platform.index' => true, 'platform.selfbio.blog.view' => true]), 'admin');

        $this->get('/admin/account/users')->assertForbidden();
        $this->post('/admin/account/users/create/save', ['user' => ['name' => 'X', 'email' => 'x@example.test', 'password' => 'a-long-password-1']])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'x@example.test'], 'pgsql');
    }

    /** @param  array<string, bool>|null  $permissions */
    private function admin(?array $permissions = null): AdminUser
    {
        $permissions ??= [
            'platform.index' => true,
            'platform.selfbio.settings.view' => true, 'platform.selfbio.settings.edit' => true,
            'platform.selfbio.contacts.view' => true, 'platform.selfbio.contacts.edit' => true,
            'platform.selfbio.blog.view' => true, 'platform.selfbio.blog.edit' => true,
            'platform.selfbio.account.view' => true, 'platform.selfbio.account.edit' => true,
            'platform.systems.users' => true, 'platform.systems.roles' => true,
        ];

        return AdminUser::query()->create([
            'name' => 'Test Admin',
            'email' => uniqid('admin-', true).'@example.test',
            'password' => 'test-password',
            'permissions' => $permissions,
        ]);
    }
}
