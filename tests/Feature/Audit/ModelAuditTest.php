<?php

namespace Tests\Feature\Audit;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_administered_model_logs_a_created_action(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);
        $category = Category::factory()->create();

        $log = AuditLog::query()
            ->where('action', 'category.created')
            ->where('auditable_id', $category->id)
            ->firstOrFail();

        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(Category::class, $log->auditable_type);
        $this->assertNull($log->old_values);
        $this->assertSame($category->name, $log->new_values['name']);
    }

    public function test_updating_a_model_logs_the_old_and_new_diff_only(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Avant']);

        $this->actingAs($admin);
        $category->update(['name' => 'Après']);

        $log = AuditLog::query()
            ->where('action', 'category.updated')
            ->where('auditable_id', $category->id)
            ->firstOrFail();

        $this->assertSame(['name' => 'Avant'], $log->old_values);
        $this->assertSame(['name' => 'Après'], $log->new_values);
        // The diff must not carry untouched columns such as the slug.
        $this->assertArrayNotHasKey('slug', $log->new_values);
    }

    public function test_deleting_a_model_logs_a_deleted_action_with_its_values(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $categoryId = $category->id;

        $this->actingAs($admin);
        $category->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'category.deleted',
            'auditable_type' => Category::class,
            'auditable_id' => $categoryId,
            'user_id' => $admin->id,
        ]);
    }

    public function test_no_op_update_does_not_create_a_log(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Stable']);

        $this->actingAs($admin);
        $category->update(['name' => 'Stable']);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'category.updated',
            'auditable_id' => $category->id,
        ]);
    }

    public function test_user_password_is_redacted_in_the_stored_values(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin);
        $created = User::factory()->create();

        $log = AuditLog::query()
            ->where('action', 'user.created')
            ->where('auditable_id', $created->id)
            ->firstOrFail();

        $this->assertSame('[redacted]', $log->new_values['password']);
        $this->assertArrayNotHasKey('plain', (array) $log->new_values);
    }

    public function test_user_role_change_is_audited_but_a_pure_2fa_update_is_not(): void
    {
        $super = User::factory()->superAdmin()->create();
        $user = User::factory()->create();

        $this->actingAs($super);

        // Account-data change → audited.
        $user->update(['role' => Role::Admin]);
        // Auth-managed-only change → handled by the auth listeners, not here.
        $user->forceFill(['two_factor_secret' => 'abc'])->save();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.updated',
            'auditable_id' => $user->id,
        ]);
        $this->assertSame(
            1,
            AuditLog::where('action', 'user.updated')->where('auditable_id', $user->id)->count(),
        );
    }
}
