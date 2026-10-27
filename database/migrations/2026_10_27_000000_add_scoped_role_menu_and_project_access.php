<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes authorization configurable rather than tied to role or project names.
 *
 * The schema is supported by the mysql, pgsql and sqlite Laravel grammars.
 * PostgreSQL additionally receives a database-side client/project consistency
 * check; the matching SQL file is for installations managed outside Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // null means a platform/system role; a value owns the custom role.
            $table->foreignId('client_id')->nullable()->after('type')->constrained('clients')->nullOnDelete();
            $table->boolean('is_super_admin')->default(false)->after('client_id');
            $table->boolean('is_assignable')->default(true)->after('is_super_admin');
            $table->index(['client_id', 'is_assignable'], 'roles_client_assignable_idx');
        });

        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('navigation_menus')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('route_name')->nullable();
            $table->string('resource')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_project_scoped')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['parent_id', 'sort_order'], 'navigation_menus_tree_idx');
            $table->index(['is_active', 'is_project_scoped'], 'navigation_menus_visibility_idx');
        });

        // A role can see a menu independently from its CRUD permissions.
        Schema::create('role_has_menu_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('navigation_menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->boolean('can_view')->default(true);
            $table->timestamps();

            $table->unique(['role_id', 'navigation_menu_id'], 'role_menu_access_unique');
        });

        /*
         * A project role is deliberately separate from model_has_roles.  The
         * latter remains useful for platform-wide roles; this table prevents a
         * role granted for one client/project from leaking into another one.
         */
        Schema::create('user_has_project_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'project_id', 'role_id'], 'user_project_role_unique');
            $table->index(['project_id', 'role_id'], 'user_project_role_lookup_idx');
        });

        // PostgreSQL also guards direct SQL/imports. Other drivers receive the
        // same validation through ProjectRoleAssignmentService.
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION ensure_user_project_role_client_match()
                RETURNS TRIGGER AS $$
                DECLARE
                    user_client_id BIGINT;
                    project_client_id BIGINT;
                    role_client_id BIGINT;
                BEGIN
                    SELECT client_id INTO user_client_id FROM users WHERE id = NEW.user_id;
                    SELECT client_id INTO project_client_id FROM resq_projects WHERE id = NEW.project_id;
                    SELECT client_id INTO role_client_id FROM roles WHERE id = NEW.role_id;

                    IF user_client_id IS NOT NULL AND user_client_id IS DISTINCT FROM project_client_id THEN
                        RAISE EXCEPTION 'User % and project % belong to different clients', NEW.user_id, NEW.project_id;
                    END IF;

                    IF role_client_id IS NOT NULL AND role_client_id IS DISTINCT FROM project_client_id THEN
                        RAISE EXCEPTION 'Role % and project % belong to different clients', NEW.role_id, NEW.project_id;
                    END IF;

                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;

                CREATE TRIGGER user_project_role_client_match
                BEFORE INSERT OR UPDATE OF user_id, project_id, role_id ON user_has_project_roles
                FOR EACH ROW EXECUTE FUNCTION ensure_user_project_role_client_match();
                SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS user_project_role_client_match ON user_has_project_roles; DROP FUNCTION IF EXISTS ensure_user_project_role_client_match();');
        }

        Schema::dropIfExists('user_has_project_roles');
        Schema::dropIfExists('role_has_menu_access');
        Schema::dropIfExists('navigation_menus');

        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex('roles_client_assignable_idx');
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['is_super_admin', 'is_assignable']);
        });
    }
};
