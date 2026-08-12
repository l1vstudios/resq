<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvincesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'id' => 1,
                'name' => 'Nanggroe Aceh Darussalam',
                'latitude' => '5.5483000',
                'longitude' => '95.3238000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 2,
                'name' => 'Sumatera Utara',
                'latitude' => '3.5952000',
                'longitude' => '98.6722000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 3,
                'name' => 'Sumatera Selatan',
                'latitude' => '-2.9761000',
                'longitude' => '104.7754000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 4,
                'name' => 'Sumatera Barat',
                'latitude' => '-0.9471000',
                'longitude' => '100.4172000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 5,
                'name' => 'Bengkulu',
                'latitude' => '-3.8004000',
                'longitude' => '102.2655000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 6,
                'name' => 'Riau',
                'latitude' => '0.5071000',
                'longitude' => '101.4478000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 7,
                'name' => 'Kepulauan Riau',
                'latitude' => '0.9186000',
                'longitude' => '104.4665000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 8,
                'name' => 'Jambi',
                'latitude' => '-1.6101000',
                'longitude' => '103.6131000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 9,
                'name' => 'Lampung',
                'latitude' => '-5.3971000',
                'longitude' => '105.2668000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 10,
                'name' => 'Bangka Belitung',
                'latitude' => '-2.1291000',
                'longitude' => '106.1138000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 11,
                'name' => 'Kalimantan Barat',
                'latitude' => '-0.0263000',
                'longitude' => '109.3425000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 12,
                'name' => 'Kalimantan Timur',
                'latitude' => '-0.5022000',
                'longitude' => '117.1536000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 13,
                'name' => 'Kalimantan Selatan',
                'latitude' => '-3.4424000',
                'longitude' => '114.8324000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 14,
                'name' => 'Kalimantan Tengah',
                'latitude' => '-2.2096000',
                'longitude' => '113.9213000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 15,
                'name' => 'Kalimantan Utara',
                'latitude' => '2.8375000',
                'longitude' => '117.3653000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 16,
                'name' => 'Banten',
                'latitude' => '-6.1201000',
                'longitude' => '106.1503000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 17,
                'name' => 'DKI Jakarta',
                'latitude' => '-6.2088000',
                'longitude' => '106.8456000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 18,
                'name' => 'Jawa Barat',
                'latitude' => '-6.9175000',
                'longitude' => '107.6191000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 19,
                'name' => 'Jawa Tengah',
                'latitude' => '-6.9667000',
                'longitude' => '110.4167000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 20,
                'name' => 'Daerah Istimewa Yogyakarta',
                'latitude' => '-7.7956000',
                'longitude' => '110.3695000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 21,
                'name' => 'Jawa Timur',
                'latitude' => '-7.2575000',
                'longitude' => '112.7521000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 22,
                'name' => 'Bali',
                'latitude' => '-8.6500000',
                'longitude' => '115.2167000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 23,
                'name' => 'Nusa Tenggara Timur',
                'latitude' => '-10.1772000',
                'longitude' => '123.6070000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 24,
                'name' => 'Nusa Tenggara Barat',
                'latitude' => '-8.5833000',
                'longitude' => '116.1167000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 25,
                'name' => 'Gorontalo',
                'latitude' => '0.5435000',
                'longitude' => '123.0568000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 26,
                'name' => 'Sulawesi Barat',
                'latitude' => '-2.6748000',
                'longitude' => '118.8945000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 27,
                'name' => 'Sulawesi Tengah',
                'latitude' => '-0.9003000',
                'longitude' => '119.8780000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 28,
                'name' => 'Sulawesi Utara',
                'latitude' => '1.4748000',
                'longitude' => '124.8421000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 29,
                'name' => 'Sulawesi Tenggara',
                'latitude' => '-3.9985000',
                'longitude' => '122.5120000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 30,
                'name' => 'Sulawesi Selatan',
                'latitude' => '-5.1477000',
                'longitude' => '119.4327000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 31,
                'name' => 'Maluku Utara',
                'latitude' => '0.7324000',
                'longitude' => '127.5625000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 32,
                'name' => 'Maluku',
                'latitude' => '-3.6954000',
                'longitude' => '128.1814000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 33,
                'name' => 'Papua Barat',
                'latitude' => '-0.8615000',
                'longitude' => '134.0620000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 34,
                'name' => 'Papua',
                'latitude' => '-2.5337000',
                'longitude' => '140.7181000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 35,
                'name' => 'Papua Tengah',
                'latitude' => '-3.3639000',
                'longitude' => '135.5000000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 36,
                'name' => 'Papua Pegunungan',
                'latitude' => '-4.0836000',
                'longitude' => '138.9481000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 37,
                'name' => 'Papua Selatan',
                'latitude' => '-8.4991000',
                'longitude' => '140.4040000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ],
            [
                'id' => 38,
                'name' => 'Papua Barat Daya',
                'latitude' => '-0.8762000',
                'longitude' => '131.2558000',
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14'
            ]
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($data as $row) {
            DB::table('provinces')->insertOrIgnore($row);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}