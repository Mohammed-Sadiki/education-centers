<?php

namespace Database\Seeders\Demo;

use App\Models\Attendance;
use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoAcademicSeeder extends Seeder
{
    use DemoSeederSupport;

    private const TIME_SLOTS = [
        ['09:00:00', '11:00:00'],
        ['11:15:00', '13:15:00'],
        ['14:00:00', '16:00:00'],
        ['16:15:00', '18:15:00'],
    ];

    public function run(): void
    {
        foreach (Center::query()->whereIn('code', ['BERLIN', 'MUNICH'])->orderBy('id')->get() as $center) {
            $rooms = $this->ensureRooms($center);
            $classes = $this->orderedClasses($center);

            $schedules = $this->seedSchedules($center, $classes, $rooms);
            $this->seedAttendances($center, $schedules);
            $this->seedEvaluations($center, $classes, $schedules);
        }
    }

    /**
     * @return Collection<int, ClassRoom>
     */
    private function orderedClasses(Center $center): Collection
    {
        $classesByCode = ClassRoom::query()
            ->where('center_id', $center->id)
            ->with(['formation', 'teachers'])
            ->get()
            ->keyBy('code');

        return collect(DemoFixtures::classBlueprints($center))
            ->map(fn (array $blueprint) => $classesByCode->get($blueprint['code']))
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, Room>
     */
    private function ensureRooms(Center $center): Collection
    {
        $rooms = Room::query()
            ->where('center_id', $center->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        $index = $rooms->count() + 1;
        $cc = DemoFixtures::centerCode($center);

        while ($rooms->count() < 3) {
            $room = Room::query()->updateOrCreate(
                [
                    'center_id' => $center->id,
                    'code' => sprintf('DEMO-%s-ROOM-%02d', $cc, $index),
                ],
                [
                    'name' => sprintf('Atelier de démonstration %s %02d', DemoFixtures::centerLabel($center), $index),
                    'description' => 'Salle créée pour les séances de démonstration.',
                    'capacity' => 30,
                    'floor' => 'Rez-de-chaussée',
                    'building' => 'Bâtiment Formation',
                    'equipment' => 'Vidéoprojecteur, tableau blanc, Wi-Fi',
                    'status' => 'active',
                ],
            );

            $rooms->push($room);
            $index++;
        }

        return $rooms->take(3)->values();
    }

    /**
     * @param  Collection<int, ClassRoom>  $classes
     * @param  Collection<int, Room>  $rooms
     * @return Collection<int, Schedule>
     */
    private function seedSchedules(Center $center, Collection $classes, Collection $rooms): Collection
    {
        $dates = $center->code === 'BERLIN'
            ? ['2026-09-14', '2026-10-05', '2026-11-09']
            : ['2026-09-15', '2026-10-06', '2026-11-10'];

        $referenceDate = Carbon::parse('2026-10-25');
        $targets = [];

        foreach ($classes as $classIndex => $class) {
            /** @var Teacher|null $teacher */
            $teacher = $class->teachers->first();

            if (! $teacher) {
                $teacher = Teacher::query()
                    ->where('center_id', $center->id)
                    ->where('domain', $class->formation?->domain)
                    ->orderBy('id')
                    ->first();
            }

            if (! $teacher || ! $class->formation) {
                continue;
            }

            $room = $rooms[$classIndex % $rooms->count()];
            [$start, $end] = self::TIME_SLOTS[intdiv($classIndex, 3) % count(self::TIME_SLOTS)];

            foreach ($dates as $sessionIndex => $date) {
                $targets[] = [
                    'center_id' => $center->id,
                    'class_id' => $class->id,
                    'teacher_id' => $teacher->id,
                    'room_id' => $room->id,
                    'date' => $date,
                    'start_time' => $start,
                    'end_time' => $end,
                    'title' => sprintf(
                        '%s – Séance %d',
                        $class->formation->name,
                        $sessionIndex + 1,
                    ),
                    'type' => $sessionIndex === 2 ? 'workshop' : 'course',
                    'description' => sprintf(
                        'Séance de démonstration du groupe %s (%s). Contenu adapté au domaine %s.',
                        $class->code,
                        DemoFixtures::centerLabel($center),
                        DemoFixtures::domainLabel($class->formation->domain),
                    ),
                    'status' => Carbon::parse($date)->lt($referenceDate) ? 'completed' : 'scheduled',
                ];
            }
        }

        $existing = Schedule::query()
            ->where('center_id', $center->id)
            ->orderBy('id')
            ->get();

        $saved = collect();

        foreach ($targets as $index => $target) {
            $schedule = $existing->get($index);

            if ($schedule) {
                $schedule->update($target);
            } else {
                $schedule = Schedule::query()->create($target);
            }

            $saved->push($schedule);
        }

        return $saved;
    }

    /**
     * @param  Collection<int, Schedule>  $schedules
     */
    private function seedAttendances(Center $center, Collection $schedules): void
    {
        $recorderId = $this->centerAdminId($center);

        foreach ($schedules as $schedule) {
            if ($schedule->status !== 'completed') {
                continue;
            }

            $enrollments = Enrollment::query()
                ->where('center_id', $center->id)
                ->where('class_id', $schedule->class_id)
                ->where('status', Enrollment::STATUS_ACCEPTED)
                ->get();

            foreach ($enrollments as $enrollment) {
                $seed = (int) $schedule->id + (int) $enrollment->student_id;
                $status = 'present';
                $arrivalTime = null;
                $lateMinutes = null;
                $justification = null;
                $justificationStatus = null;

                if ($seed % 17 === 0) {
                    $status = 'absent';
                    $justification = 'Absence pour raison médicale (justificatif fictif).';
                    $justificationStatus = $seed % 2 === 0 ? 'approved' : 'pending';
                } elseif ($seed % 13 === 0) {
                    $status = 'late';
                    $lateMinutes = 10 + ($seed % 15);
                    $arrivalTime = Carbon::parse($schedule->start_time)
                        ->addMinutes($lateMinutes)
                        ->format('H:i:s');
                    $justification = 'Retard lié aux transports (donnée fictive).';
                    $justificationStatus = 'approved';
                }

                Attendance::query()->updateOrCreate(
                    [
                        'student_id' => $enrollment->student_id,
                        'schedule_id' => $schedule->id,
                    ],
                    [
                        'center_id' => $center->id,
                        'class_id' => $schedule->class_id,
                        'enrollment_id' => $enrollment->id,
                        'date' => $schedule->date,
                        'status' => $status,
                        'arrival_time' => $arrivalTime,
                        'late_minutes' => $lateMinutes,
                        'justification' => $justification,
                        'comment' => $status === 'present' ? 'Présence enregistrée en démonstration.' : null,
                        'justification_status' => $justificationStatus,
                        'recorded_by' => $recorderId,
                    ],
                );
            }
        }
    }

    /**
     * @param  Collection<int, ClassRoom>  $classes
     * @param  Collection<int, Schedule>  $schedules
     */
    private function seedEvaluations(Center $center, Collection $classes, Collection $schedules): void
    {
        foreach ($classes as $classIndex => $class) {
            if (! $class->formation) {
                continue;
            }

            /** @var Teacher|null $teacher */
            $teacher = $class->teachers->first();
            $classSchedules = $schedules->where('class_id', $class->id)->values();
            $firstSchedule = $classSchedules->first();

            $targets = [
                [
                    'title' => 'Évaluation diagnostique',
                    'type' => 'control',
                    'evaluation_date' => Carbon::parse('2026-09-21')->addDays($classIndex)->toDateString(),
                    'max_grade' => 20,
                    'coefficient' => 1,
                    'description' => 'Évaluation de positionnement en début de parcours.',
                    'status' => 'published',
                ],
                [
                    'title' => 'Contrôle des acquis',
                    'type' => 'exam',
                    'evaluation_date' => Carbon::parse('2026-10-19')->addDays($classIndex)->toDateString(),
                    'max_grade' => 20,
                    'coefficient' => 2,
                    'description' => 'Contrôle des compétences professionnelles du premier semestre.',
                    'status' => 'published',
                ],
            ];

            $existing = Evaluation::query()
                ->where('center_id', $center->id)
                ->where('class_id', $class->id)
                ->orderBy('id')
                ->get();

            $usedIds = [];
            $evaluations = [];

            foreach ($targets as $target) {
                $evaluation = $existing->first(
                    fn (Evaluation $candidate) => $candidate->title === $target['title']
                        && ! in_array($candidate->id, $usedIds, true)
                );

                if (! $evaluation) {
                    $evaluation = $existing->first(
                        fn (Evaluation $candidate) => ! in_array($candidate->id, $usedIds, true)
                    );
                }

                $attributes = $target + [
                    'center_id' => $center->id,
                    'class_id' => $class->id,
                    'formation_id' => $class->formation_id,
                    'teacher_id' => $teacher?->id,
                    'schedule_id' => $firstSchedule?->id,
                ];

                if ($evaluation) {
                    $evaluation->update($attributes);
                } else {
                    $evaluation = Evaluation::query()->create($attributes);
                }

                $usedIds[] = $evaluation->id;
                $evaluations[] = $evaluation;
            }

            $enrollments = Enrollment::query()
                ->where('center_id', $center->id)
                ->where('class_id', $class->id)
                ->where('status', Enrollment::STATUS_ACCEPTED)
                ->get();

            foreach ($evaluations as $evaluation) {
                foreach ($enrollments as $enrollment) {
                    $seed = (int) $evaluation->id + (int) $enrollment->student_id;
                    $isAbsent = $seed % 23 === 0;
                    $grade = $isAbsent ? null : 8 + ($seed % 12);

                    Grade::query()->updateOrCreate(
                        [
                            'evaluation_id' => $evaluation->id,
                            'student_id' => $enrollment->student_id,
                        ],
                        [
                            'center_id' => $center->id,
                            'enrollment_id' => $enrollment->id,
                            'grade' => $grade,
                            'comment' => $isAbsent
                                ? 'Absence justifiée lors de l’évaluation (donnée fictive).'
                                : 'Note de démonstration générée de façon déterministe.',
                            'graded_at' => Carbon::parse($evaluation->evaluation_date)->addDays(7)->toDateString(),
                            'is_absent' => $isAbsent,
                        ],
                    );
                }
            }
        }
    }
}
