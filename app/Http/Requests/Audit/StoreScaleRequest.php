<?php

namespace App\Http\Requests\Audit;

use Illuminate\Foundation\Http\FormRequest;


class StoreScaleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'label' => 'required|string|min:3|max:255|unique:audit_factor_scales,label',
            'min_value' => 'required|integer',
            'max_value' => 'required|integer|gt:min_value',
            'description' => 'nullable|string|max:1000',
            'factor_id' => 'nullable|integer|exists:audit_factors,id',
        ];
    }

    public function messages()
    {
        return [
            'label.required' => 'Le libellé est requis',
            'label.unique' => 'Ce libellé existe déjà',
            'min_value.required' => 'La valeur minimum est requise',
            'max_value.required' => 'La valeur maximum est requise',
            'max_value.gt' => 'La valeur maximum doit être supérieure à la minimum',
        ];
    }
}
