<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation de la mise à jour d'un document.
 *
 * On ne permet pas de changer le fichier lui-même dans la mise à jour
 * (l'upload est une opération distincte). Si tu veux le permettre plus
 * tard, créer une route POST /documents/{id}/replace avec sa propre
 * validation. Cela évite les surprises sur update.
 *
 * On ne permet jamais de modifier service_id / department_id / direction_id /
 * company_id : ils ne peuvent être définis qu'à la création.
 */
class DocumentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:191'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'document_type_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('document_types', 'id')->where(function ($q) {
                    $q->where('company_id', $this->user()->company_id);
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire.',
            'title.max' => 'Le titre ne doit pas dépasser 191 caractères.',
            'document_type_id.required' => 'Le type de document est obligatoire.',
            'document_type_id.exists' => "Le type de document sélectionné n'appartient pas à votre entreprise.",
        ];
    }
}
