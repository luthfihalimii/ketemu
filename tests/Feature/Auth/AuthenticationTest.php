<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_student_can_register_and_is_logged_in(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@student.pens.ac.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'budi@student.pens.ac.id')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(Role::Student, $user->role);
        $this->assertFalse(Hash::needsRehash($user->password));
    }

    #[Test]
    public function registration_never_accepts_a_client_supplied_role(): void
    {
        $this->post(route('register'), [
            'name' => 'Penyusup',
            'email' => 'penyusup@student.pens.ac.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'admin',
        ]);

        $user = User::query()->where('email', 'penyusup@student.pens.ac.id')->firstOrFail();

        $this->assertSame(Role::Student, $user->role);
        $this->assertFalse($user->isAdmin());
    }

    #[Test]
    public function registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ada@student.pens.ac.id']);

        $this->post(route('register'), [
            'name' => 'Duplikat',
            'email' => 'ada@student.pens.ac.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('email');
    }

    #[Test]
    public function registration_requires_a_strong_password(): void
    {
        $this->post(route('register'), [
            'name' => 'Lemah',
            'email' => 'lemah@student.pens.ac.id',
            'password' => 'abcdefgh',
            'password_confirmation' => 'abcdefgh',
        ])->assertSessionHasErrors('password');
    }

    #[Test]
    public function a_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'rahasia123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function login_errors_do_not_reveal_whether_the_email_exists(): void
    {
        $message = null;

        $this->post(route('login'), [
            'email' => 'tidak-ada@student.pens.ac.id',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $message = session('errors')->first('email');

        $this->assertSame('Email atau password salah.', $message);

        User::factory()->create(['email' => 'ada@student.pens.ac.id', 'password' => 'rahasia123']);

        $this->post(route('login'), [
            'email' => 'ada@student.pens.ac.id',
            'password' => 'salah-sekali',
        ])->assertSessionHasErrors('email');

        $this->assertSame('Email atau password salah.', session('errors')->first('email'));
    }

    #[Test]
    public function login_is_rate_limited_per_email_and_ip(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'salah']);
        }

        $this->post(route('login'), ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertSessionHasErrors('email');

        $this->assertSame(
            'Terlalu banyak percobaan masuk. Silakan coba lagi nanti.',
            session('errors')->first('email'),
        );

        $this->assertGuest();
    }

    #[Test]
    public function a_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));

        $this->assertGuest();
    }

    #[Test]
    public function guests_are_redirected_to_login_for_protected_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('items.create-found'))->assertRedirect(route('login'));
        $this->get(route('items.create-lost'))->assertRedirect(route('login'));
    }
}
