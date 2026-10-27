<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_french_is_the_default_locale_and_renders_left_to_right(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('dir="ltr"', false);
    }

    public function test_arabic_switch_persists_in_session_and_renders_rtl(): void
    {
        $this->from('/')
            ->get(route('locale.switch', 'ar'))
            ->assertRedirect('/');

        $this->assertSame('ar', session('locale'));

        $this->get('/')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('العربية');
    }

    public function test_switching_back_to_french_restores_ltr(): void
    {
        $this->get(route('locale.switch', 'ar'));
        $this->get(route('locale.switch', 'fr'));

        $this->get('/')
            ->assertOk()
            ->assertSee('dir="ltr"', false);
    }

    public function test_validation_messages_are_translated_in_arabic(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);

        $errors = session('errors');

        $this->assertSame('البريد الإلكتروني مطلوب.', $errors->first('email'));
        $this->assertSame('كلمة المرور مطلوبة.', $errors->first('password'));
    }

    public function test_custom_validation_messages_are_translated_in_arabic(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'status' => 'active',
            'locale' => 'ar',
        ]);

        $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->post(route('centers.store'), [])
            ->assertSessionHasErrors([
                'name' => 'اسم المركز مطلوب.',
            ]);
    }

    public function test_authenticated_user_locale_is_restored_after_login(): void
    {
        $center = Center::factory()->create();

        $user = User::factory()->centerAdmin($center->id)->create([
            'email' => 'localized.admin@example.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'locale' => 'ar',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.home'));

        // /dashboard redirige désormais vers l'accueil commun (4 cartes)
        $this->get(route('dashboard'))
            ->assertRedirect(route('admin.home'));

        $this->get(route('admin.home'))
            ->assertOk()
            ->assertSee('dir="rtl"', false);
    }

    public function test_guest_language_choice_is_kept_after_login(): void
    {
        $center = Center::factory()->create();

        $user = User::factory()->centerAdmin($center->id)->create([
            'email' => 'guest.choice@example.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'locale' => 'fr',
        ]);

        $this->get(route('locale.switch', 'ar'));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.home'));

        $this->assertSame('ar', $user->fresh()->locale);

        $this->get(route('admin.home'))
            ->assertOk()
            ->assertSee('dir="rtl"', false);
    }

    public function test_locale_preference_is_saved_when_switching_while_authenticated(): void
    {
        $center = Center::factory()->create();
        $user = User::factory()->centerAdmin($center->id)->create([
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('locale.switch', 'ar'))
            ->assertRedirect();

        $this->assertSame('ar', $user->fresh()->locale);
    }

    public function test_super_admin_pages_render_in_arabic(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'status' => 'active',
            'locale' => 'ar',
        ]);

        $this->actingAs($admin)
            ->get(route('super-admin.domaines'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('العربية');
    }

    public function test_center_admin_dashboard_renders_in_arabic(): void
    {
        $center = Center::factory()->create();
        $admin = User::factory()->centerAdmin($center->id)->create([
            'status' => 'active',
            'locale' => 'ar',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.home'));

        $this->actingAs($admin)
            ->get(route('admin.home'))
            ->assertOk()
            ->assertSee('dir="rtl"', false);
    }
}
