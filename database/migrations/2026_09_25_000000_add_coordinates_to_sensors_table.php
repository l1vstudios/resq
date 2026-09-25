<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            if (! Schema::hasColumn('sensors', 'coordinate')) {
                $table->string('coordinate')->nullable();
            }

            if (! Schema::hasColumn('sensors', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }

            if (! Schema::hasColumn('sensors', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $columns = collect(['coordinate', 'latitude', 'longitude'])
                ->filter(fn (string $column) => Schema::hasColumn('sensors', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
