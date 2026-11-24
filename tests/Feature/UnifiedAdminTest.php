<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UnifiedAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_single_admin_login(): void
    {
        $this->get('/admin/selfbio/blog')->assertRedirect('/admin/login');
    }

    public function test_selfbio_admin_can_save_public_content(): void
    {
        Storage::fake('public');
        $admin = $this->admin([
            'platform.index' => true,
            'platform.selfbio.blog.view' => true,
            'platform.selfbio.blog.edit' => true,
            'platform.selfbio.contacts.view' => true,
            'platform.selfbio.contacts.edit' => true,
        ]);
        $this->assertTrue($admin->hasAccess('platform.selfbio.blog.view'));
        $this->actingAs($admin, 'admin');

        $this->get('/admin/selfbio/blog')->assertOk();
        $this->get('/admin/selfbio/contacts')->assertOk();
        $this->get('/admin/main')->assertOk()->assertSee('SelfBio');

        $this->post('/admin/selfbio/contacts/save', ['contact' => [
            'name' => 'SelfBio', 'email' => 'contact@example.test', 'socials' => '[]',
        ]])->assertRedirect('/admin/selfbio/contacts');
        $this->assertDatabaseHas('contact_info', ['email' => 'contact@example.test'], 'pgsql');

        $this->post('/admin/selfbio/blog/create/save', ['post' => [
            'title' => 'Статья', 'slug' => 'article', 'preview' => 'Анонс',
            'body' => 'Текст', 'published_at' => now()->subDay()->format('Y-m-d\TH:i'),
        ], 'image_upload' => UploadedFile::fake()->image('cover.jpg')])->assertRedirect();
        $this->assertDatabaseHas('blog_posts', ['slug' => 'article'], 'pgsql');
        $image = DB::connection('pgsql')->table('blog_posts')->where('slug', 'article')->value('image');
        $this->assertNotNull($image);
        Storage::disk('public')->assertExists($image);

        $postId = DB::connection('pgsql')->table('blog_posts')->where('slug', 'article')->value('id');
        $this->get('/admin/selfbio/blog')->assertOk();
        $this->get("/admin/selfbio/blog/{$postId}/edit")->assertOk();
        $this->post("/admin/selfbio/blog/{$postId}/edit/save", ['post' => [
            'title' => 'Обновлённая статья', 'slug' => 'article', 'preview' => 'Анонс',
            'body' => 'Текст', 'published_at' => now()->subDay()->format('Y-m-d\TH:i'),
        ]])->assertRedirect("/admin/selfbio/blog/{$postId}/edit");
        $this->assertDatabaseHas('blog_posts', ['id' => $postId, 'title' => 'Обновлённая статья'], 'pgsql');

        $this->getJson('/api/contacts')->assertOk()->assertJsonPath('email', 'contact@example.test');
        $this->getJson('/api/blog/article')->assertOk()->assertJsonPath('title', 'Обновлённая статья');
    }

    public function test_moonshine_import_keeps_existing_orchid_password_and_is_idempotent(): void
    {
        $admin = $this->admin(['platform.index' => true]);
        $password = $admin->password;
        $db = DB::connection('pgsql');
        $roleId = $db->table('moonshine_user_roles')->value('id');
        $db->table('moonshine_users')->insert([
            'name' => 'Old Admin', 'email' => $admin->email,
            'password' => Hash::make('old-password'), 'moonshine_user_role_id' => $roleId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('selfbio:import-admins', ['--apply' => true])->assertExitCode(0);
        $this->artisan('selfbio:import-admins', ['--apply' => true])->assertExitCode(0);

        $role = $db->table('roles')->where('slug', 'selfbio-admin')->first();
        $this->assertNotNull($role);
        $this->assertEquals(1, $db->table('role_users')->where('user_id', $admin->id)->where('role_id', $role->id)->count());
        $this->assertSame($password, $db->table('users')->where('id', $admin->id)->value('password'));
    }

    public function test_moonshine_import_grants_the_account_section_and_drops_the_welcome_keys(): void
    {
        $db = DB::connection('pgsql');
        $db->table('roles')->updateOrInsert(['slug' => 'selfbio-admin'], [
            'name' => 'SelfBio admin',
            'permissions' => json_encode(['platform.selfbio.welcome.edit' => true], JSON_THROW_ON_ERROR),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('selfbio:import-admins', ['--apply' => true])->assertExitCode(0);

        $permissions = json_decode((string) $db->table('roles')->where('slug', 'selfbio-admin')->value('permissions'), true);
        $this->assertTrue($permissions['platform.selfbio.account.view']);
        $this->assertTrue($permissions['platform.selfbio.account.edit']);
        $this->assertArrayNotHasKey('platform.selfbio.welcome.edit', $permissions);
    }

    private function admin(array $permissions): AdminUser
    {
        return AdminUser::query()->create([
            'name' => 'Test Admin',
            'email' => uniqid('admin-', true).'@example.test',
            'password' => 'test-password',
            'permissions' => $permissions,
        ]);
    }
}
