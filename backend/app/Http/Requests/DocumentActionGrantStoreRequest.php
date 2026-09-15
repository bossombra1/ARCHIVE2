<?php

namespace App\Http\Requests;

use App\Models\DocumentActionGrant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation de l'octroi (bulk) de droits d'action documentaire par
 * l'Administrateur Système à un ou plusieurs utilisateurs simultanément.
 *
 * L'autorisation elle-même (seul poste.level = admin peut appeler cette
 * route) est garantie par le middleware `poste:admin` sur le groupe de
 * routes (cf. routes/api.php) — pas de Policy dédiée nécessaire ici.
 */
class DocumentActionGrantStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
            'can_modify' => ['boolean'],
            'can_delete' => ['boolean'],
            'can_add' => ['boolean'],
            'scope' => ['required', Rule::in(DocumentActionGrant::SCOPES)],
            'document_ids' => ['required_if:scope,specific', 'array'],
            'document_ids.*' => [
                'integer',
                Rule::exists('documents', 'id')->where(
                    fn ($query) => $query->where('company_id', $companyId)->whereNull('deleted_at')
                ),
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required' => 'Sélectionnez au moins un utilisateur.',
            'user_ids.array' => 'Le format des utilisateurs sélectionnés est invalide.',
            'user_ids.min' => 'Sélectionnez au moins un utilisateur.',
            'user_ids.*.exists' => "Un des utilisateurs sélectionnés n'existe pas dans votre entreprise.",
            'scope.required' => 'La portée (tous les documents / documents précis) est obligatoire.',
            'scope.in' => "La portée doit être 'all' ou 'specific'.",
            'document_ids.required_if' => "Sélectionnez au moins un document pour une portée 'specific'.",
            'document_ids.*.exists' => "Un des documents sélectionnés n'existe pas (ou est en corbeille) dans votre entreprise.",
            'expires_at.after' => "La date d'expiration doit être dans le futur.",
        ];
    }

    /**
     * Au moins un des trois droits doit être accordé — sinon la requête
     * n'a pas de sens (autant révoquer le grant via destroy()).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->boolean('can_modify') && ! $this->boolean('can_delete') && ! $this->boolean('can_add')) {
                $validator->errors()->add('can_modify', 'Accordez au moins un droit (modifier, supprimer ou ajouter).');
            }
        });
    }
}