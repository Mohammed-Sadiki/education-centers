<?php

namespace App\Services;

use App\Models\Schedule;
use Illuminate\Support\Collection;

class ScheduleConflictService
{
    /**
     * Vérifie s'il existe des conflits pour une séance (professeur, salle, classe).
     *
     * Formule de chevauchement sur la même date :
     * existing.start_time < new.end_time AND existing.end_time > new.start_time
     *
     * @param  int|string  $date
     * @param  string  $startTime  Format H:i
     * @param  string  $endTime  Format H:i
     * @param  int|null  $ignoreScheduleId  (pour la modification)
     * @return array<string, string> Liste des conflits rencontrés (clé => message clair)
     */
    public function checkConflicts(
        $date,
        string $startTime,
        string $endTime,
        int $teacherId,
        int $roomId,
        int $classId,
        ?int $ignoreScheduleId = null
    ): array {
        $conflicts = [];

        // Conflit Professeur : le même professeur ne peut pas avoir deux séances qui se chevauchent
        $teacherConflict = Schedule::query()
            ->where('teacher_id', $teacherId)
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreScheduleId, fn ($q) => $q->where('id', '!=', $ignoreScheduleId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->first();

        if ($teacherConflict) {
            $conflicts['teacher_id'] = sprintf(
                __('Impossible de créer cette séance : le professeur est déjà occupé de %s à %s.'),
                substr($teacherConflict->start_time, 0, 5),
                substr($teacherConflict->end_time, 0, 5)
            );
        }

        // Conflit Salle : une salle ne peut pas être utilisée simultanément par deux séances
        $roomConflict = Schedule::query()
            ->where('room_id', $roomId)
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreScheduleId, fn ($q) => $q->where('id', '!=', $ignoreScheduleId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->first();

        if ($roomConflict) {
            $conflicts['room_id'] = sprintf(
                __('Impossible de réserver cette salle : elle est déjà utilisée de %s à %s.'),
                substr($roomConflict->start_time, 0, 5),
                substr($roomConflict->end_time, 0, 5)
            );
        }

        // Conflit Classe : une classe ne doit pas avoir deux séances simultanées
        $classConflict = Schedule::query()
            ->where('class_id', $classId)
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreScheduleId, fn ($q) => $q->where('id', '!=', $ignoreScheduleId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->first();

        if ($classConflict) {
            $conflicts['class_id'] = sprintf(
                __('Impossible : la classe possède déjà une séance sur ce créneau (%s à %s).'),
                substr($classConflict->start_time, 0, 5),
                substr($classConflict->end_time, 0, 5)
            );
        }

        return $conflicts;
    }

    /**
     * Identifie, parmi une collection de séances déjà chargées, celles qui sont
     * en conflit (chevauchement horaire le même jour partageant un même
     * professeur, une même salle ou une même classe).
     *
     * Sert à signaler visuellement les conflits sur le planning sans requête
     * supplémentaire : la comparaison se fait en mémoire sur le jeu filtré.
     *
     * @param  Collection<int, Schedule>  $schedules
     * @return array<int, int> Liste d'identifiants de séances en conflit
     */
    public function overlappingIds(Collection $schedules): array
    {
        // On ignore les séances annulées qui ne bloquent aucun créneau.
        $items = $schedules
            ->reject(fn (Schedule $s): bool => $s->isCancelled())
            ->values()
            ->all();

        $conflicting = [];
        $count = count($items);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $items[$i];
                $b = $items[$j];

                if (! $a->date || ! $b->date || ! $a->date->isSameDay($b->date)) {
                    continue;
                }

                // Chevauchement strict sur la même journée.
                $overlaps = $a->start_time < $b->end_time && $b->start_time < $a->end_time;
                if (! $overlaps) {
                    continue;
                }

                $sharesResource = $a->teacher_id === $b->teacher_id
                    || $a->room_id === $b->room_id
                    || $a->class_id === $b->class_id;

                if ($sharesResource) {
                    $conflicting[$a->id] = true;
                    $conflicting[$b->id] = true;
                }
            }
        }

        return array_keys($conflicting);
    }
}
