<?php

namespace Database\Seeders\Demo;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\EnrollmentStatusService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoEnrollmentsSeeder extends Seeder
{
    public function run(): void
    {
        $statusService = app(EnrollmentStatusService::class);

        foreach (Center::query()->whereIn('code', ['BERLIN', 'MUNICH'])->orderBy('id')->get() as $center) {
            // Anciens doublons de démonstration : ils sont annulés (jamais
            // supprimés) afin qu'un étudiant n'ait qu'une inscription active et
            // que le calcul financier par étudiant reste cohérent.
            foreach (['BERLIN-INS-000002', 'MUNICH-INS-000002'] as $registrationNumber) {
                $duplicate = Enrollment::query()
                    ->where('center_id', $center->id)
                    ->where('registration_number', $registrationNumber)
                    ->first();

                if ($duplicate && $duplicate->status !== Enrollment::STATUS_CANCELLED) {
                    $statusService->changeStatus(
                        $duplicate,
                        Enrollment::STATUS_CANCELLED,
                        'Inscription de test annulée : doublon de démonstration remplacé par une inscription active unique.',
                    );
                }
            }

            $classesByCode = ClassRoom::query()
                ->where('center_id', $center->id)
                ->get()
                ->keyBy('code');

            $classesByDomain = [];
            $classBlueprints = DemoFixtures::classBlueprints($center);

            foreach ($classBlueprints as $blueprint) {
                $class = $classesByCode->get($blueprint['code']);

                if ($class) {
                    $classesByDomain[$blueprint['domain']][] = $class;
                }
            }

            $students = Student::query()
                ->where('center_id', $center->id)
                ->orderBy('student_number')
                ->get();

            $studentsById = $students->keyBy('id');

            $activeEnrollments = Enrollment::query()
                ->where('center_id', $center->id)
                ->whereIn('status', [
                    Enrollment::STATUS_ACCEPTED,
                    Enrollment::STATUS_PENDING,
                    Enrollment::STATUS_UNDER_REVIEW,
                ])
                ->with('formation')
                ->orderByDesc('id')
                ->get()
                ->groupBy('student_id');

            $domainStudents = [];
            $assignedStudentIds = [];

            foreach (DemoFixtures::domainSlugs() as $domain) {
                $domainStudents[$domain] = [];

                foreach ($students as $student) {
                    $enrollment = $activeEnrollments->get($student->id)?->first();

                    if ($enrollment && $enrollment->formation?->domain === $domain) {
                        $domainStudents[$domain][] = $student;
                        $assignedStudentIds[$student->id] = true;
                    }
                }
            }

            // Mohammed (étudiant historique sans inscription active) est
            // rattaché à l'hôtellerie pour compléter la répartition demandée.
            foreach ($students as $student) {
                if (isset($assignedStudentIds[$student->id])) {
                    continue;
                }

                $hasActive = $activeEnrollments->has($student->id);

                if ($hasActive) {
                    continue;
                }

                $defaultDomain = $student->student_number === 'BERLIN-000002'
                    ? 'hotellerie'
                    : ($center->code === 'BERLIN' ? 'informatique' : 'sante');

                if (count($domainStudents[$defaultDomain]) < 10) {
                    $domainStudents[$defaultDomain][] = $student;
                    $assignedStudentIds[$student->id] = true;
                }
            }

            $unassigned = $students
                ->reject(fn (Student $student) => isset($assignedStudentIds[$student->id]))
                ->values();

            foreach (DemoFixtures::domainSlugs() as $domain) {
                while (count($domainStudents[$domain]) < 10 && $unassigned->isNotEmpty()) {
                    /** @var Student $student */
                    $student = $unassigned->shift();
                    $domainStudents[$domain][] = $student;
                    $assignedStudentIds[$student->id] = true;
                }
            }

            foreach (DemoFixtures::domainSlugs() as $domain) {
                $domainClasses = $classesByDomain[$domain] ?? [];

                if (count($domainClasses) < 2) {
                    continue;
                }

                $domainStudentList = collect($domainStudents[$domain])
                    ->unique('id')
                    ->take(10)
                    ->values();

                foreach ($domainStudentList as $index => $student) {
                    /** @var ClassRoom $class */
                    $class = $index < 5 ? $domainClasses[0] : $domainClasses[1];
                    $existing = $activeEnrollments->get($student->id)?->first();

                    if ($existing) {
                        // Une inscription active est immuable : la déplacer
                        // créerait des présences et notes orphelines et
                        // rendrait la commande non idempotente.
                        if ($existing->class_id === null) {
                            $existing->update([
                                'class_id' => $class->id,
                                'formation_id' => $class->formation_id,
                            ]);
                        }

                        continue;
                    }

                    $cc = DemoFixtures::centerCode($center);
                    $registrationNumber = str_starts_with($student->student_number, $cc.'-DEMO-')
                        ? sprintf('DEMO-%s-INS-%s', $cc, substr($student->student_number, -4))
                        : sprintf('DEMO-%s-INS-EXTRA-%d', $cc, $student->id);

                    $status = Enrollment::STATUS_ACCEPTED;
                    $number = (int) preg_replace('/\D+/', '', $student->student_number);
                    $number = $number > 0 ? $number : $student->id;

                    if ($number % 19 === 7) {
                        $status = Enrollment::STATUS_PENDING;
                    } elseif ($number % 29 === 13) {
                        $status = Enrollment::STATUS_UNDER_REVIEW;
                    }

                    $applicationDate = Carbon::parse('2026-08-20')
                        ->addDays(min(55, $index * 2))
                        ->toDateString();
                    $decisionDate = $status === Enrollment::STATUS_ACCEPTED
                        ? Carbon::parse($applicationDate)->addDays(5)->toDateString()
                        : null;
                    $acceptedAt = $status === Enrollment::STATUS_ACCEPTED
                        ? Carbon::parse($decisionDate)->setTime(10, 0)
                        : null;

                    Enrollment::query()->updateOrCreate(
                        ['registration_number' => $registrationNumber],
                        [
                            'center_id' => $center->id,
                            'student_id' => $student->id,
                            'formation_id' => $class->formation_id,
                            'class_id' => $class->id,
                            'school_year' => DemoFixtures::SCHOOL_YEAR,
                            'application_date' => $applicationDate,
                            'status' => $status,
                            'notes' => 'Inscription fictive de démonstration pour l’année '.DemoFixtures::SCHOOL_YEAR.'.',
                            'decision_date' => $decisionDate,
                            'decision_notes' => $status === Enrollment::STATUS_ACCEPTED
                                ? 'Dossier accepté après entretien de positionnement.'
                                : 'Dossier en cours de traitement administratif.',
                            'accepted_at' => $acceptedAt,
                            'cancelled_at' => null,
                        ],
                    );
                }
            }
        }
    }
}
