<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StitchesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('stitches')->truncate(); // Bersihkan tabel dulu

        $startDate = Carbon::create(2025, 1, 1); // Mulai dari 1 Januari 2025
        $endDate = Carbon::create(2025, 4, 26);  // Sampai 26 April 2025

        $dates = [];

        // Generate semua tanggal dari start sampai end
        while ($startDate <= $endDate) {
            $dates[] = $startDate->copy();
            $startDate->addDay();
        }

        foreach ($dates as $date) {
            // Bikin 5 data per hari biar banyak variasi
            for ($i = 0; $i < 5; $i++) {
                // Generate waktu random pada setiap entry
                $randomTime = $date->copy()->setTime(rand(0, 23), rand(0, 59), rand(0, 59));

                DB::table('stitches')->insert([
                    'good' => rand(50, 100),           // Random good stitches
                    'bad' => rand(0, 10),              // Random bad stitches
                    'created_at' => now(),             // Waktu update otomatis
                    'updated_at' => now(),             // Waktu update otomatis
                    'time' => $randomTime->format('Y-m-d H:i:s'),  // Format Y-m-d H:i:s untuk time
                ]);
            }
        }
    }
}
