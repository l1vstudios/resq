<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tde_matrix_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('resq_projects')->nullOnDelete();
            $table->string('matrix_code')->unique();
            $table->string('name');
            $table->string('version_label')->nullable();
            $table->string('source_filename')->nullable();
            $table->json('matrix_rows');
            $table->json('import_summary')->nullable();
            $table->foreignId('imported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['project_id', 'status'], 'tde_matrix_project_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tde_matrix_versions');
    }
};
