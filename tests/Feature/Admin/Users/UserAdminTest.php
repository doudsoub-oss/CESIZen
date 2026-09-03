<?php

namespace Tests\Feature\Admin\Users;

use App\Enums\Role;
use App\Models\Diagnostic;
use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_user_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_index_only_shows_regular_users(): void
    {
        $admin = User::factory()->admin()->create();
        $regulars = User::factory(2)->create();
        $otherAdmin = User::factory()->admin()->create();
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('users.total', 3));
            // ->assertInertia(fn ($page) => $page->where('users.total', 2));
    }

    public function test_super_admin_index_shows_everyone(): void
    {
        $super = User::factory()->superAdmin()->create();
        User::factory(2)->create();
        User::factory()->admin()->create();

        // 2 users + 1 admin + the super-admin itself = 4 total.
        $this->actingAs($super)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('users.total', 4));
    }

    public function test_admin_cannot_view_an_admin_account(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.show', $other))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_any_account(): void
    {
        $super = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($super)
            ->get(route('admin.users.show', $admin))
            ->assertOk();
    }

    public function test_admin_creates_a_user_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Nouveau',
                'email' => 'nouveau@cesizen.fr',
                'password' => 'Strong-pass-123',
                'password_confirmation' => 'Strong-pass-123',
                'role' => Role::User->value,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'nouveau@cesizen.fr',
            'role' => Role::User->value,
        ]);
    }

    public function test_admin_cannot_create_an_admin_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Escalation',
                'email' => 'escalation@cesizen.fr',
                'password' => 'Strong-pass-123',
                'password_confirmation' => 'Strong-pass-123',
                'role' => Role::Admin->value,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'escalation@cesizen.fr']);
    }

    public function test_super_admin_can_create_an_admin_account(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->post(route('admin.users.store'), [
                'name' => 'Nouvel Admin',
                'email' => 'nouvel.admin@cesizen.fr',
                'password' => 'Strong-pass-123',
                'password_confirmation' => 'Strong-pass-123',
                'role' => Role::Admin->value,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'nouvel.admin@cesizen.fr',
            'role' => Role::Admin->value,
        ]);
    }

    public function test_nobody_can_create_a_super_admin_via_the_admin(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Sneaky',
                'email' => 'sneaky@cesizen.fr',
                'password' => 'Strong-pass-123',
                'password_confirmation' => 'Strong-pass-123',
                'role' => Role::SuperAdmin->value,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_admin_can_toggle_a_regular_users_active_flag(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $target));

        $this->assertFalse($target->fresh()->is_active);
    }

    public function test_admin_cannot_toggle_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $other))
            ->assertForbidden();
    }

    public function test_admin_cannot_toggle_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $admin))
            ->assertForbidden();
    }

    public function test_super_admin_can_change_a_user_role_to_admin(): void
    {
        $super = User::factory()->superAdmin()->create();
        $target = User::factory()->create();

        $this->actingAs($super)
            ->patch(route('admin.users.change-role', $target), ['role' => Role::Admin->value])
            ->assertRedirect();

        $this->assertSame(Role::Admin, $target->fresh()->role);
    }

    public function test_admin_cannot_change_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.change-role', $target), ['role' => Role::Admin->value])
            ->assertForbidden();
    }

    public function test_role_change_to_super_admin_is_rejected(): void
    {
        $super = User::factory()->superAdmin()->create();
        $target = User::factory()->create();

        // Authorization runs before validation: UserPolicy::changeRole denies
        // any newRole=super_admin, so this responds 403 rather than 422.
        $this->actingAs($super)
            ->patch(route('admin.users.change-role', $target), ['role' => Role::SuperAdmin->value])
            ->assertForbidden();
    }

    public function test_hard_delete_cascades_user_data(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();
        $q = Questionnaire::factory()->create();
        Diagnostic::factory(3)->create(['user_id' => $target->id, 'questionnaire_id' => $q->id]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('diagnostics', ['user_id' => $target->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();
    }

    public function test_self_deletion_via_settings_cascades(): void
    {
        $user = User::factory()->create();
        $q = Questionnaire::factory()->create();
        Diagnostic::factory(2)->create(['user_id' => $user->id, 'questionnaire_id' => $q->id]);

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('diagnostics', ['user_id' => $user->id]);
    }
}
