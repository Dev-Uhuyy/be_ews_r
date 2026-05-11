<?php

namespace Database\Seeders;

use App\Models\KhsKrsMahasiswa;
use Illuminate\Database\Seeder;

class UpdateAbsenSeeder extends Seeder
{
    public function run(): void
    {
        $count = 0;
        $updated = 0;

        KhsKrsMahasiswa::chunk(500, function ($khsList) use (&$count, &$updated) {
            foreach ($khsList as $khs) {
                $count++;
                if ($khs->absen === null) {
                    $khs->update(['absen' => round(rand(0, 16) * 6.25)]);
                    $updated++;
                }
            }
        });

        $this->command->info("Total KHS processed: {$count}");
        $this->command->info("Total absen updated: {$updated}");
    }
}
