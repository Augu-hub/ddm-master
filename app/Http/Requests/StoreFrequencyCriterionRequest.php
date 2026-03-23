<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFrequencyCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'designation' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order'  => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'designation.required' => 'La désignation du critère est obligatoire.',
            'designation.max'      => 'La désignation ne peut pas dépasser 200 caractères.',
        ];
    }
}
