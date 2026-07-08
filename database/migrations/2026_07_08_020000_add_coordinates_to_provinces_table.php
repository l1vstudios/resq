<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('name');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        foreach ($this->coordinates() as $name => $coordinate) {
            DB::table('provinces')->where('name', $name)->update([
                'latitude' => $coordinate[0],
                'longitude' => $coordinate[1],
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }

    private function coordinates(): array
    {
        return [
            'Nanggroe Aceh Darussalam' => [5.5483, 95.3238],
            'Sumatera Utara' => [3.5952, 98.6722],
            'Sumatera Selatan' => [-2.9761, 104.7754],
            'Sumatera Barat' => [-0.9471, 100.4172],
            'Bengkulu' => [-3.8004, 102.2655],
            'Riau' => [0.5071, 101.4478],
            'Kepulauan Riau' => [0.9186, 104.4665],
            'Jambi' => [-1.6101, 103.6131],
            'Lampung' => [-5.3971, 105.2668],
            'Bangka Belitung' => [-2.1291, 106.1138],
            'Kalimantan Barat' => [-0.0263, 109.3425],
            'Kalimantan Timur' => [-0.5022, 117.1536],
            'Kalimantan Selatan' => [-3.4424, 114.8324],
            'Kalimantan Tengah' => [-2.2096, 113.9213],
            'Kalimantan Utara' => [2.8375, 117.3653],
            'Banten' => [-6.1201, 106.1503],
            'DKI Jakarta' => [-6.2088, 106.8456],
            'Jawa Barat' => [-6.9175, 107.6191],
            'Jawa Tengah' => [-6.9667, 110.4167],
            'Daerah Istimewa Yogyakarta' => [-7.7956, 110.3695],
            'Jawa Timur' => [-7.2575, 112.7521],
            'Bali' => [-8.6500, 115.2167],
            'Nusa Tenggara Timur' => [-10.1772, 123.6070],
            'Nusa Tenggara Barat' => [-8.5833, 116.1167],
            'Gorontalo' => [0.5435, 123.0568],
            'Sulawesi Barat' => [-2.6748, 118.8945],
            'Sulawesi Tengah' => [-0.9003, 119.8780],
            'Sulawesi Utara' => [1.4748, 124.8421],
            'Sulawesi Tenggara' => [-3.9985, 122.5120],
            'Sulawesi Selatan' => [-5.1477, 119.4327],
            'Maluku Utara' => [0.7324, 127.5625],
            'Maluku' => [-3.6954, 128.1814],
            'Papua Barat' => [-0.8615, 134.0620],
            'Papua' => [-2.5337, 140.7181],
            'Papua Tengah' => [-3.3639, 135.5000],
            'Papua Pegunungan' => [-4.0836, 138.9481],
            'Papua Selatan' => [-8.4991, 140.4040],
            'Papua Barat Daya' => [-0.8762, 131.2558],
        ];
    }
};
