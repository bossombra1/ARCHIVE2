<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation de la création d'un document.
 *
 * Important : $this->user() détermine le périmètre côté serveur.
 * Les champs service_id / department_id / direction_id / company_id ne sont
 * JAMAIS pris depuis le frontend : ils sont établis par le contrôleur à partir
 * de l'affectation active de l'utilisateur connecté.
 */
class DocumentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // L'autorisation est gérée par la DocumentPolicy dans le contrôleur.
    }

    public function rules(): array
    {
        $allowedExt = config('documents.allowed_extensions', ['pdf', 'png', 'jpg', 'jpeg', 'gif']);
        $maxKb = (int) config('documents.max_size_kb', 10240);

        return [
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:5000'],
            'document_type_id' => [
                'required',
                'integer',
                Rule::exists('document_types', 'id')->where(function ($q) {
                    $q->where('company_id', $this->user()->company_id);
                }),
            ],
            'file' => [
                'required',
                'file',
                'max:' . $maxKb,
                'mimes:' . implode(',', $allowedExt),
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
            'file.required' => 'Le fichier est obligatoire.',
            'file.max' => 'La taille du fichier dépasse la limite autorisée.',
            'file.mimes' => "Le type de fichier n'est pas autorisé. Types acceptés : pdf, png, jpg, jpeg, gif.",
        ];
    }
}
