<?php

namespace Tests\Feature\Console;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MakeStaffCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_guard_account(): void
    {
        $this->artisan('ketemupens:make-staff', [
            'email' => 'satpam.d4@ketemupens.test',
            '--role' => 'guard',
            '--name' => 'Satpam Pos D4',
            '--password' => 'Rahasia123',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'satpam.d4@ketemupens.test')->firstOrFail();

        $this->assertSame(Role::Guard, $user->role);
        $this->assertSame('Satpam Pos D4', $user->name);
        $this->assertTrue(Hash::check('Rahasia123', $user->password));
    }

    #[Test]
    public function it_creates_an_admin_account(): void
    {
        $this->artisan('ketemupens:make-staff', [
            'email' => 'admin@ketemupens.test',
            '--role' => 'admin',
            '--password' => 'Rahasia123',
        ])->assertSuccessful();

        $this->assertSame(Role::Admin, User::query()->firstOrFail()->role);
    }

    #[Test]
    public function it_defaults_to_guard_and_derives_a_name_from_the_email(): void
    {
        $this->artisan('ketemupens:make-staff', [
            'email' => 'satpam.pos-d3@ketemupens.test',
            '--password' => 'Rahasia123',
        ])->assertSuccessful();

        $user = User::query()->firstOrFail();

        $this->assertSame(Role::Guard, $user->role);
        $this->assertSame('Satpam Pos D3', $user->name);
    }

    #[Test]
    public function it_marks_the_account_as_verified(): void
    {
        $this->artisan('ketemupens:make-staff', [
            'email' => 'satpam@ketemupens.test',
            '--password' => 'Rahasia123',
        ])->assertSuccessful();

        $this->assertNotNull(User::query()->firstOrFail()->email_verified_at);
    }

    #[Test]
    public function it_refuses_an_email_that_already_exists(): void
    {
        User::factory()->create(['email' => 'admin@ketemupens.test']);

        $this->artisan('ketemupens:make-staff', [
            'email' => 'admin@ketemupens.test',
            '--role' => 'admin',
            '--password' => 'Rahasia123',
        ])->assertFailed();

        $this->assertSame(1, User::query()->count());
    }

    #[Test]
    public function it_rejects_an_invalid_role(): void
    {
        $this->artisan('ketemupens:make-staff', [
            'email' => 'x@ketemupens.test',
            '--role' => 'student',
            '--password' => 'Rahasia123',
        ])->assertFailed();

        $this->assertSame(0, User::query()->count());
    }

    #[Test]
    public function it_rejects_a_weak_password(): void
    {
        $this->artisan('ketemupens:make-staff', [
            'email' => 'x@ketemupens.test',
            '--role' => 'guard',
            '--password' => 'abc',
        ])->assertFailed();

        $this->assertSame(0, User::query()->count());
    }

    #[Test]
    public function it_requires_a_password_when_running_non_interactively(): void
    {
        $this->artisan('ketemupens:make-staff', [
            'email' => 'x@ketemupens.test',
            '--role' => 'guard',
            '--no-interaction' => true,
        ])->assertFailed();

        $this->assertSame(0, User::query()->count());
    }

    #[Test]
    public function it_asks_for_the_password_when_none_is_provided(): void
    {
        $this->artisan('ketemupens:make-staff', [
            'email' => 'satpam@ketemupens.test',
            '--role' => 'guard',
        ])
            ->expectsQuestion('Password', 'Rahasia123')
            ->expectsQuestion('Konfirmasi password', 'Rahasia123')
            ->assertSuccessful();

        $user = User::query()->firstOrFail();

        $this->assertTrue(Hash::check('Rahasia123', $user->password));
    }

    #[Test]
    public function a_provisioned_guard_can_open_the_pickup_panel(): void
    {
        $this->artisan('ketemupens:make-staff', [
            'email' => 'satpam@ketemupens.test',
            '--role' => 'guard',
            '--password' => 'Rahasia123',
        ])->assertSuccessful();

        $guard = User::query()->firstOrFail();

        // The whole point of the command: the account is immediately usable.
        $this->actingAs($guard)
            ->get(route('guard.pickup.create'))
            ->assertOk();
    }

    #[Test]
    public function a_provisioned_admin_can_open_the_moderation_queue(): void
    {
        $this->artisan('ketemupens:make-staff', [
            'email' => 'admin@ketemupens.test',
            '--role' => 'admin',
            '--password' => 'Rahasia123',
        ])->assertSuccessful();

        $this->actingAs(User::query()->firstOrFail())
            ->get(route('admin.items.index'))
            ->assertOk();
    }

    #[Test]
    public function it_records_the_provisioning_in_the_audit_trail(): void
    {
        $this->artisan('ketemupens:make-staff', [
            'email' => 'satpam@ketemupens.test',
            '--role' => 'guard',
            '--password' => 'Rahasia123',
        ])->assertSuccessful();

        $user = User::query()->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.staff_created',
            'auditable_type' => $user->getMorphClass(),
            'auditable_id' => $user->id,
            // Console runs have no authenticated actor.
            'user_id' => null,
        ]);

        $properties = AuditLog::query()->firstOrFail()->properties;

        $this->assertSame('guard', $properties['role']);
    }
}
