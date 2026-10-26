<?php

namespace Database\Seeders\Demo;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Formation;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoClassesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Center::query()->whereIn('code', ['BERLIN', 'MUNICH'])->orderBy('id')->get() as $center) {
            $legacyMap = DemoFixtures::legacyClassCodes($center);

            // Réutilisation des classes existantes sans suppression.
            foreach ($legacyMap as $legacyCode => $targetCode) {
                $targetExists = ClassRoom::query()
                    ->where('center_id', $center->id)
                    ->where('code', $targetCode)
                    ->exists();

                if ($targetExists) {
                    continue;
                }

                $legacy = ClassRoom::query()
                    ->where('center_id', $center->id)
                    ->where('code', $legacyCode)
                    ->first();

                if ($legacy) {
                    $legacy->update(['code' => $targetCode]);
                }
            }

            $formations = Formation::query()
                ->where('center_id', $center->id)
                ->get()
                ->keyBy('code');

            $teacherBlueprints = DemoFixtures::teacherBlueprints($center);
            $teachers = Teacher::query()
                ->where('center_id', $center->id)
                ->get()
                ->keyBy('teacher_number');

            foreach (DemoFixtures::classBlueprints($center) as $index => $blueprint) {
                $formation = $formations->get($blueprint['formation_code']);

                if (! $formation) {
                    continue;
                }

                $class = ClassRoom::query()->updateOrCreate(
                    [
                        'center_id' => $center->id,
                        'code' => $blueprint['code'],
                    ],
                    [
                        'formation_id' => $formation->id,
                        'name' => $blueprint['name'],
                        'description' => $blueprint['description'],
                        'capacity' => $blueprint['capacity'],
                        'start_date' => $blueprint['start_date'],
                        'end_date' => $blueprint['end_date'],
                        'status' => 'active',
                    ],
                );

                $teacherNumber = $teacherBlueprints[$index]['teacher_number'] ?? null;
                $teacher = $teacherNumber ? $teachers->get($teacherNumber) : null;

                if (! $teacher) {
                    continue;
                }

                $pivots = DB::table('class_teacher')
                    ->where('class_id', $class->id)
                    ->orderBy('id')
                    ->get();

                if ($pivots->contains(fn ($pivot) => (int) $pivot->teacher_id === (int) $teacher->id)) {
                    continue;
                }

                $extra = $pivots->first();

                if ($extra) {
                    DB::table('class_teacher')
                        ->where('id', $extra->id)
                        ->update([
                            'teacher_id' => $teacher->id,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('class_teacher')->insert([
                        'class_id' => $class->id,
                        'teacher_id' => $teacher->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
