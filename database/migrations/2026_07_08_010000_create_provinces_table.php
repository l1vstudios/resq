<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        DB::table('provinces')->insert(array_map(fn ($name) => [
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ], [
            'Nanggroe Aceh Darussalam',
            'Sumatera Utara',
            'Sumatera Selatan',
            'Sumatera Barat',
            'Bengkulu',
            'Riau',
            'Kepulauan Riau',
            'Jambi',
            'Lampung',
            'Bangka Belitung',
            'Kalimantan Barat',
            'Kalimantan Timur',
            'Kalimantan Selatan',
            'Kalimantan Tengah',
            'Kalimantan Utara',
            'Banten',
            'DKI Jakarta',
            'Jawa Barat',
            'Jawa Tengah',
            'Daerah Istimewa Yogyakarta',
            'Jawa Timur',
            'Bali',
            'Nusa Tenggara Timur',
            'Nusa Tenggara Barat',
            'Gorontalo',
            'Sulawesi Barat',
            'Sulawesi Tengah',
            'Sulawesi Utara',
            'Sulawesi Tenggara',
            'Sulawesi Selatan',
            'Maluku Utara',
            'Maluku',
            'Papua Barat',
            'Papua',
            'Papua Tengah',
            'Papua Pegunungan',
            'Papua Selatan',
            'Papua Barat Daya',
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
