<?php

namespace Database\Seeders;

use App\Models\ProdiUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProdiUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = [1982, 1983, 1984];

        foreach ($userIds as $userId) {
            // Assign specific users to Prodi ID 1 (Teknik Informatika)
            ProdiUser::firstOrCreate(
                ['user_id' => $userId],
                ['prodi_id' => 1]
            );
        }
    }
}
