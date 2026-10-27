<?php

namespace App\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * Règles de validation partagées par les formulaires « Ajouter un stagiaire »
 * et « Modifier un stagiaire ».
 *
 * Principe : toutes les informations du stagiaire sont obligatoires, SAUF les
 * deux sections facultatives « Diplômes obtenus » et « Autres documents ».
 * Une ligne dynamique laissée vide est ignorée (elle n'est pas considérée comme
 * un ajout) ; dès qu'un administrateur renseigne une ligne, tous les champs de
 * cette ligne — texte et fichier scanné — deviennent obligatoires.
 */
trait HasStagiaireValidation
{
    /**
     * Champs des sections 1 à 3 : obligatoires et formatés.
     *
     * Les fichiers CIN ne figurent pas ici : leur caractère obligatoire diffère
     * entre la création (toujours exigés) et la modification (un scan déjà
     * enregistré peut être conservé).
     */
    protected function stagiaireFieldRules(): array
    {
        return [
            // 1. Informations personnelles
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'center_id' => ['required', 'integer', 'exists:centers,id'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['male', 'female', 'other'])],
            'nationality' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+().\s-]+$/'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:255'],

            // 2. Identité (CIN)
            'cin_number' => ['required', 'string', 'max:50'],
            'cin_issued_date' => ['required', 'date'],
            'cin_issued_place' => ['required', 'string', 'max:255'],

            // 3. Stage, période et prix convenu
            'stage_start_date' => ['required', 'date'],
            'stage_end_date' => ['required', 'date', 'after_or_equal:stage_start_date'],
            'stage_type' => ['required', 'string', 'max:255'],
            'stage_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['required', 'string', 'max:10'],
            'status' => ['required', 'string', Rule::in(['nouveau', 'en_cours', 'termine', 'suspendu', 'annule'])],
            'notes' => ['required', 'string', 'max:2000'],

            // Alias envoyés par le formulaire d'édition : facultatifs en tant que
            // tels, leur valeur est reversée dans les champs canoniques ci-dessus
            // par prepareForValidation().
            'birth_date' => ['nullable', 'date', 'before:today'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'cin_delivered_at' => ['nullable', 'date'],
            'cin_delivered_place' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Règles conditionnelles des lignes dynamiques (sections 4 et 5).
     *
     * @return array<string, array>
     */
    protected function attachmentRowRules(): array
    {
        $rules = [
            'diplomas' => ['nullable', 'array'],
            'diplomas.*' => ['array'],
            'other_documents' => ['nullable', 'array'],
            'other_documents.*' => ['array'],
        ];

        foreach ($this->dynamicRows('diplomas') as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if ($this->rowIsFilled($row, ['title', 'institution', 'year'], "diplomas.{$index}.file")) {
                $rules["diplomas.{$index}.title"] = ['required', 'string', 'max:255'];
                $rules["diplomas.{$index}.institution"] = ['required', 'string', 'max:255'];
                $rules["diplomas.{$index}.year"] = ['required', 'integer', 'min:1900', 'max:2100'];
                $rules["diplomas.{$index}.file"] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];
            }
        }

        foreach ($this->dynamicRows('other_documents') as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            // Le « type de pièce » est présélectionné dans le formulaire : lui seul
            // ne doit pas transformer une ligne vide en document ajouté.
            if ($this->rowIsFilled($row, ['title'], "other_documents.{$index}.file")) {
                $rules["other_documents.{$index}.title"] = ['required', 'string', 'max:255'];
                $rules["other_documents.{$index}.type"] = ['required', 'string',
                    Rule::in(['convention', 'attestation', 'certificat', 'cv', 'autre'])];
                $rules["other_documents.{$index}.file"] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];
            }
        }

        return $rules;
    }

    /**
     * Libellés des champs utilisés dans les messages d'erreur du formulaire.
     */
    protected function stagiaireAttributes(): array
    {
        return [
            'first_name' => __('prénom'),
            'last_name' => __('nom'),
            'center_id' => __('centre'),
            'date_of_birth' => __('date de naissance'),
            'place_of_birth' => __('lieu de naissance'),
            'gender' => __('sexe'),
            'nationality' => __('nationalité'),
            'phone' => __('téléphone'),
            'email' => __('adresse e-mail'),
            'address' => __('adresse'),
            'cin_number' => __('numéro CIN'),
            'cin_issued_date' => __('date de délivrance CIN'),
            'cin_issued_place' => __('lieu de délivrance CIN'),
            'cin_recto' => __('CIN recto'),
            'cin_verso' => __('CIN verso'),
            'stage_start_date' => __('date de début du stage'),
            'stage_end_date' => __('date de fin du stage'),
            'stage_type' => __('type de stage'),
            'stage_price' => __('prix du stage'),
            'currency' => __('devise'),
            'status' => __('statut'),
            'notes' => __('notes'),
            'diplomas.*.title' => __('nom du diplôme'),
            'diplomas.*.institution' => __('établissement du diplôme'),
            'diplomas.*.year' => __('année d\'obtention du diplôme'),
            'diplomas.*.file' => __('fichier du diplôme'),
            'other_documents.*.title' => __('titre du document'),
            'other_documents.*.type' => __('type de pièce'),
            'other_documents.*.file' => __('fichier du document'),
        ];
    }

    /**
     * Messages explicites pour les sections facultatives.
     */
    protected function stagiaireMessages(): array
    {
        return [
            'stage_price.numeric' => __('Le prix du stage doit être un montant numérique valide.'),
            'stage_price.min' => __('Le prix du stage doit être supérieur ou égal à 0.'),
            'stage_end_date.after_or_equal' => __('La date de fin du stage doit être postérieure ou égale à la date de début.'),
            'phone.regex' => __('Le numéro de téléphone est invalide.'),
            'diplomas.*.file.required' => __('Ce diplôme nécessite son fichier scanné (PDF ou image).'),
            'diplomas.*.title.required' => __('Le nom du diplôme est obligatoire lorsque la ligne est utilisée.'),
            'diplomas.*.institution.required' => __('L\'établissement est obligatoire lorsque la ligne est utilisée.'),
            'diplomas.*.year.required' => __('L\'année d\'obtention est obligatoire lorsque la ligne est utilisée.'),
            'other_documents.*.file.required' => __('Ce document nécessite son fichier scanné (PDF ou image).'),
            'other_documents.*.title.required' => __('Le titre / la description est obligatoire lorsque la ligne est utilisée.'),
            'other_documents.*.type.required' => __('Le type de pièce est obligatoire lorsque la ligne est utilisée.'),
        ];
    }

    /**
     * Lignes réellement présentes dans la requête pour une section dynamique.
     */
    private function dynamicRows(string $key): array
    {
        $rows = $this->input($key);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Une ligne est considérée « ajoutée » dès qu'un de ses champs utiles est
     * renseigné ou qu'un fichier y a été téléversé. Les valeurs laissées à vide
     * par défaut (champ texte vide, type présélectionné non compté) ne la
     * rendent pas obligatoire.
     */
    private function rowIsFilled(array $row, array $textFields, string $filePath): bool
    {
        foreach ($textFields as $field) {
            if (trim((string) ($row[$field] ?? '')) !== '') {
                return true;
            }
        }

        return $this->hasUpload($filePath);
    }

    /**
     * Un champ file de formulaire vide n'est pas téléversé : on ne le compte pas.
     */
    private function hasUpload(string $path): bool
    {
        $file = data_get($this->allFiles(), $path);

        return $file instanceof UploadedFile && $file->getError() !== UPLOAD_ERR_NO_FILE;
    }
}
