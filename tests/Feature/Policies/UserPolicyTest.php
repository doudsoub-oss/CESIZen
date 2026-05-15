<?php

namespace Tests\Feature\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy;
    }

    public function test_regular_user_cannot_perform_any_admin_action_on_users(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();

        $this->assertFalse($this->policy->viewAny($actor));
        $this->assertFalse($this->policy->view($actor, $target));
        $this->assertFalse($this->policy->create($actor));
        $this->assertFalse($this->policy->update($actor, $target));
        $this->assertFalse($this->policy->delete($actor, $target));
    }

    public function test_admin_can_manage_regular_users_only(): void
    {
        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $otherAdmin = User::factory()->admin()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin, $regular));
        $this->assertFalse($this->policy->view($admin, $otherAdmin));
        $this->assertFalse($this->policy->view($admin, $superAdmin));

        $this->assertTrue($this->policy->update($admin, $regular));
        $this->assertFalse($this->policy->update($admin, $otherAdmin));
        $this->assertTrue($this->policy->delete($admin, $regular));
        $this->assertFalse($this->policy->delete($admin, $otherAdmin));
    }

    public function test_super_admin_can_manage_users_and_admins_but_not_other_super_admins(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $regular = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $otherSuperAdmin = User::factory()->superAdmin()->create();

        $this->assertTrue($this->policy->view($superAdmin, $regular));
        $this->assertTrue($this->policy->view($superAdmin, $admin));
        $this->assertTrue($this->policy->view($superAdmin, $otherSuperAdmin));

        $this->assertTrue($this->policy->update($superAdmin, $regular));
        $this->assertTrue($this->policy->update($superAdmin, $admin));
        $this->assertFalse($this->policy->update($superAdmin, $otherSuperAdmin));
    }

    public function test_no_user_can_admin_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertFalse($this->policy->update($admin, $admin));
        $this->assertFalse($this->policy->delete($admin, $admin));
        $this->assertFalse($this->policy->update($superAdmin, $superAdmin));
        $this->assertFalse($this->policy->delete($superAdmin, $superAdmin));
    }

    public function test_change_role_is_super_admin_only_and_never_creates_or_modifies_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();

        // Super-admin can promote / demote between user and admin.
        $this->assertTrue($this->policy->changeRole($superAdmin, $regular, Role::Admin));
        $this->assertTrue($this->policy->changeRole($superAdmin, $admin, Role::User));

        // Cannot promote anyone to super_admin via the UI.
        $this->assertFalse($this->policy->changeRole($superAdmin, $regular, Role::SuperAdmin));
        $this->assertFalse($this->policy->changeRole($superAdmin, $admin, Role::SuperAdmin));

        // Cannot modify an existing super-admin's role.
        $otherSuper = User::factory()->superAdmin()->create();
        $this->assertFalse($this->policy->changeRole($superAdmin, $otherSuper, Role::Admin));

        // Cannot change own role.
        $this->assertFalse($this->policy->changeRole($superAdmin, $superAdmin, Role::Admin));

        // Admins cannot change roles at all.
        $this->assertFalse($this->policy->changeRole($admin, $regular, Role::Admin));
    }
}
