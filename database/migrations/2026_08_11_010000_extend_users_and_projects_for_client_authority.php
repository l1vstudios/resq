<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend users table
        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['sentinel', 'client'])->default('sentinel')->after('email_verified_at');
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete()->after('type');
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active')->after('client_id');
        });

        // Extend resq_projects table
        Schema::table('resq_projects', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete()->after('owner');
        });
    }

    public function down(): void
    {
        Schema::table('resq_projects', function (Blueprint $table) {
            $table->dropForeignIdFor('clients');
            $table->dropColumn('client_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignIdFor('clients');
            $table->dropColumn(['status', 'client_id', 'type']);
        });
    }
};
