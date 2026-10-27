<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;

/**
 * Constructeur de données du formulaire stagiaire.
 *
 * Le formulaire « Ajouter un stagiaire » exige désormais toutes ses informations
 * hors sections « Diplômes obtenus » et « Autres documents ». Les tests partagent
 * donc un jeu de données complet, qu'ils dégradent volontairement pour vérifier
 * un refus précis.
 */
trait HasStagiaireFormData
{
    /**
     * Jeu de données valide couvrant les sections obligatoires 1 à 3.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function stagiairePayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Karim',
            'last_name' => 'Benani',
            'date_of_birth' => '1998-05-14',
            'place_of_birth' => 'Casablanca',
            'gender' => 'male',
            'nationality' => 'Marocaine',
            'phone' => '+212612345678',
            'email' => 'karim.benani@example.com',
            'address' => '12 Rue de Rivoli, Paris',
            'cin_number' => 'AB123456',
            'cin_issued_date' => '2020-01-15',
            'cin_issued_place' => 'Préfecture Paris',
            'stage_start_date' => '2026-11-01',
            'stage_end_date' => '2027-04-30',
            'stage_type' => "Stage de fin d'études",
            'stage_price' => 1500.00,
            'currency' => 'EUR',
            'status' => 'nouveau',
            'notes' => 'Candidature spontanée acceptée.',
        ], $overrides);
    }

    /**
     * Les deux scans CIN exigés à la création.
     *
     * @return array<string, UploadedFile>
     */
    protected function cinFiles(): array
    {
        return [
            'cin_recto' => UploadedFile::fake()->create('cin_recto.pdf', 120, 'application/pdf'),
            'cin_verso' => UploadedFile::fake()->create('cin_verso.pdf', 120, 'application/pdf'),
        ];
    }

    /**
     * Données du formulaire + scans CIN : dossier complet accepté.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function stagiairePayloadWithCin(array $overrides = []): array
    {
        return $this->stagiairePayload($overrides) + $this->cinFiles();
    }

    /**
     * Retire un champ obligatoire (ou le vide) pour provoquer un refus.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function withoutField(array $payload, string $field): array
    {
        unset($payload[$field]);

        return $payload;
    }
}
