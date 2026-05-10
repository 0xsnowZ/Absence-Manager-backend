<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TypeAbsence;

class TypeAbsenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['code' => 'PRESENT', 'libelle' => 'Présent'],
            ['code' => 'ABSENT', 'libelle' => 'Absent non justifié'],
            ['code' => 'EXCUSED', 'libelle' => 'Absence justifiée'],
            ['code' => 'SICK', 'libelle' => 'Malade'],
            ['code' => 'PERMIT', 'libelle' => 'Permis'],
            ['code' => 'LATE', 'libelle' => 'Retard'],
            ['code' => 'PARTIAL', 'libelle' => 'Présence partielle'],
        ];

        foreach ($types as $type) {
            TypeAbsence::firstOrCreate(
                ['code' => $type['code']],
                ['libelle' => $type['libelle']]
            );
        }
    }
}
