<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CampusEmailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function registration_rejects_an_email_outside_the_campus(): void
    {
        $this->post(route('register'), [
            'name' => 'Orang Luar',
            'email' => 'orang@gmail.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'orang@gmail.com']);
    }

    #[Test]
    public function registration_accepts_any_student_program_subdomain(): void
    {
        $this->post(route('register'), [
            'name' => 'Mahasiswa TIF',
            'email' => 'budi@tif.student.pens.ac.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('users', ['email' => 'budi@tif.student.pens.ac.id']);
    }

    #[Test]
    public function registration_accepts_the_main_staff_domain(): void
    {
        $this->post(route('register'), [
            'name' => 'Dosen PENS',
            'email' => 'dosen@pens.ac.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionDoesntHaveErrors();
    }

    #[Test]
    public function a_new_registrant_receives_a_verification_email(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Mahasiswa Baru',
            'email' => 'baru@student.pens.ac.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $user = User::query()->where('email', 'baru@student.pens.ac.id')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertFalse($user->hasVerifiedEmail());
    }

    #[Test]
    public function an_unverified_user_cannot_report_or_claim(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('items.create-found'))
            ->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('items.create-lost'))
            ->assertRedirect(route('verification.notice'));
    }

    #[Test]
    public function an_unverified_user_can_still_reach_the_dashboard_and_notifications(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('notifications.index'))->assertOk();
        $this->actingAs($user)->get(route('verification.notice'))->assertOk();
    }

    #[Test]
    public function a_user_can_verify_their_email_via_the_signed_link(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($url)
            ->assertRedirect(route('dashboard'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
