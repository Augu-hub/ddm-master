<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatrixConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'matrix_size' => ['required', 'integer', 'in:3,4,5'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'matrix_size.in' => 'La taille de matrice doit être 3, 4 ou 5.',
        ];
    }
}
