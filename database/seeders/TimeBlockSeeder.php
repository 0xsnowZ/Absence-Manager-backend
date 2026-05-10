<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TimeBlock;

class TimeBlockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $blocks = [
            ['code' => 'TB1', 'label' => '08:30 → 11:00', 'heure_debut' => '08:30:00', 'heure_fin' => '11:00:00'],
            ['code' => 'TB2', 'label' => '11:00 → 13:15', 'heure_debut' => '11:00:00', 'heure_fin' => '13:15:00'],
            ['code' => 'TB3', 'label' => '13:30 → 16:00', 'heure_debut' => '13:30:00', 'heure_fin' => '16:00:00'],
            ['code' => 'TB4', 'label' => '16:00 → 18:30', 'heure_debut' => '16:00:00', 'heure_fin' => '18:30:00'],
            ['code' => 'TB5', 'label' => '19:00 → 21:00 (CDS)', 'heure_debut' => '19:00:00', 'heure_fin' => '21:00:00'],
        ];

        foreach ($blocks as $block) {
            TimeBlock::updateOrCreate(['code' => $block['code']], $block);
        }
    }
}
