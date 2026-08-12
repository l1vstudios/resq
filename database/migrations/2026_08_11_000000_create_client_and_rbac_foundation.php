<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clients (Master Client / Organization)
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_code')->unique();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');
            $table->unsignedInteger('max_users')->default(10);
            $table->unsignedInteger('max_projects')->default(5);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        // Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->default('system'); // system, custom
            $table->timestamps();

            $table->index('type');
        });

        // Permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->string('resource')->nullable(); // project, workspace, station, etc.
            $table->string('action')->nullable(); // create, read, edit, delete
            $table->timestamps();

            $table->index(['resource', 'action']);
        });

        // Role -> Permission
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        // Model (User/Client) -> Role (polymorphic)
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->morphs('model');
            $table->timestamps();

            $table->unique(['role_id', 'model_id', 'model_type']);
        });

        // User -> Project access binding
        Schema::create('user_has_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->enum('access_level', ['viewer', 'operator', 'manager'])->default('operator');
            $table->timestamps();

            $table->unique(['user_id', 'project_id']);
            $table->index(['project_id', 'access_level']);
        });

        // Project Recovery Account (Sentinel-controlled, one per project)
        Schema::create('project_recovery_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained('resq_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recovery_username')->unique();
            $table->string('recovery_password_hash');
            $table->enum('status', ['active', 'unused', 'revoked'])->default('unused');
            $table->timestamp('last_used_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_recovery_accounts');
        Schema::dropIfExists('user_has_projects');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('clients');
    }
};
