<?php

namespace App\Http\Requests;

use App\Models\DocumentPermission;
use App\Models\Poste;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation de la création d'une permission documentaire.
 *
 * Règles :
 *   - target_type ∈ {poste, service, user}
 *   - target_id doit exister et appartenir à la même company que le document
 *     (sauf target_type=poste car les postes sont globaux).
 *   - expires_at nullable (permanente) OU dans le futur.
 *
 * L'autorisation (le grantor a-t-il le droit d'accorder une permission sur
 * ce document, et la cible est-elle dans son périmètre ?) est gérée par la
 * DocumentPolicy::createPermission, appelée par le contrôleur via $this->authorize.
 */
class DocumentPermissionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $companyId = $user?->company_id;

        return [
            'target_type' => ['required', Rule::in(DocumentPermission::TARGETS)],
            'target_id' => ['required', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_type.required' => 'Le type de cible est obligatoire.',
            'target_type.in' => "Le type de cible doit être : 'user', 'poste' ou 'service'.",
            'target_id.required' => "L'identifiant de la cible est obligatoire.",
            'target_id.integer' => "L'identifiant de la cible doit être un entier.",
            'expires_at.date' => "La date d'expiration n'est pas valide.",
            'expires_at.after' => "La date d'expiration doit être dans le futur.",
        ];
    }

    /**
     * Validation personnalisée supplémentaire : la cible doit exister et
     * appartenir à la même company que l'utilisateur (pour user/service).
     * On ne rejette pas ici pour les cibles incohérentes ; le service
     * DocumentVisibilityService::isValidPermissionTarget s'en charge plus
     * finement (vérifie en plus le périmètre du grantor).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $targetType = $this->input('target_type');
            $targetId = (int) $this->input('target_id');
            $companyId = $this->user()?->company_id;

            if (! $targetType || ! $targetId) {
                return;
            }

            switch ($targetType) {
                case DocumentPermission::TARGET_USER:
                    $exists = User::where('id', $targetId)
                        ->where('company_id', $companyId)
                        ->exists();
                    if (! $exists) {
                        $validator->errors()->add('target_id', "L'utilisateur ciblé n'existe pas dans votre entreprise.");
                    }
                    break;

                case DocumentPermission::TARGET_SERVICE:
                    $exists = Service::where('id', $targetId)
                        ->where('company_id', $companyId)
                        ->exists();
                    if (! $exists) {
                        $validator->errors()->add('target_id', "Le service ciblé n'existe pas dans votre entreprise.");
                    }
                    break;

                case DocumentPermission::TARGET_POSTE:
                    $exists = Poste::where('id', $targetId)->exists();
                    if (! $exists) {
                        $validator->errors()->add('target_id', "Le poste ciblé n'existe pas.");
                    }
                    break;
            }
        });
    }
}
