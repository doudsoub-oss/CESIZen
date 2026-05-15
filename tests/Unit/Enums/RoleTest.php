<?php

namespace Tests\Unit\Enums;

use App\Enums\Role;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function test_levels_form_a_strict_hierarchy(): void
    {
        $this->assertLessThan(Role::Admin->level(), Role::User->level());
        $this->assertLessThan(Role::SuperAdmin->level(), Role::Admin->level());
    }

    public function test_super_admin_is_at_least_admin_and_user(): void
    {
        $this->assertTrue(Role::SuperAdmin->isAtLeast(Role::SuperAdmin));
        $this->assertTrue(Role::SuperAdmin->isAtLeast(Role::Admin));
        $this->assertTrue(Role::SuperAdmin->isAtLeast(Role::User));
    }

    public function test_admin_is_at_least_admin_and_user_but_not_super_admin(): void
    {
        $this->assertTrue(Role::Admin->isAtLeast(Role::Admin));
        $this->assertTrue(Role::Admin->isAtLeast(Role::User));
        $this->assertFalse(Role::Admin->isAtLeast(Role::SuperAdmin));
    }

    public function test_user_is_only_at_least_user(): void
    {
        $this->assertTrue(Role::User->isAtLeast(Role::User));
        $this->assertFalse(Role::User->isAtLeast(Role::Admin));
        $this->assertFalse(Role::User->isAtLeast(Role::SuperAdmin));
    }

    public function test_each_case_has_a_french_label(): void
    {
        $this->assertNotEmpty(Role::User->label());
        $this->assertNotEmpty(Role::Admin->label());
        $this->assertNotEmpty(Role::SuperAdmin->label());
    }
}
