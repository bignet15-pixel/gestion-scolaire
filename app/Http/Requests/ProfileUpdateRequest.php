<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Prépare les données avant validation.
     *
     * Le champ `name` reste utilisé par Laravel Breeze et par l'en-tête du site.
     * Dans ce projet, l'utilisateur possède aussi `nom` et `prenom`, donc on
     * synchronise automatiquement `name` avec "prenom nom".
     */
    protected function prepareForValidation(): void
    {
        $prenom = trim((string) $this->input('prenom', ''));
        $nom = trim((string) $this->input('nom', ''));
        $fullName = trim($prenom . ' ' . $nom);

        if ($fullName !== '') {
            $this->merge([
                'name' => $fullName,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'sexe' => ['nullable', Rule::in(['M', 'F'])],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($userId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique(User::class, 'phone')->ignore($userId),
            ],
            'adresse' => ['nullable', 'string', 'max:255'],
        ];
    }
}
