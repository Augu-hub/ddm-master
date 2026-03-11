<?php

namespace App\Http\Requests\Audit;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation pour créer un Factor
 */
class StoreFactorRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'label' => 'required|string|min:3|max:255|unique:audit_factors,label',
            'description' => 'nullable|string|max:1000',
            'importance' => 'required|integer|in:1,2,3,4,5',
            'is_active' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'label.required' => 'Le libellé est requis',
            'label.min' => 'Le libellé doit contenir au moins 3 caractères',
            'label.unique' => 'Ce libellé existe déjà',
            'importance.required' => 'L\'importance est requise',
            'importance.in' => 'L\'importance doit être entre 1 et 5',
        ];
    }
}
