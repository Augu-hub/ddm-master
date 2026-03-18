<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFrequencyLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $configId = $this->input('matrix_config_id');

        return [
            'matrix_config_id' => ['required', 'integer', 'exists:tenant.risk_matrix_configs,id'],
            'label'            => ['required', 'string', 'max:100'],
            'score'            => [
                'required',
                'integer',
                'min:1',
                'max:5',
                Rule::unique('risk_frequency_levels', 'score')
                    ->where('matrix_config_id', $configId)
                    ->whereNull('deleted_at'),
            ],
            'description'      => ['nullable', 'string', 'max:500'],
            'recurrence'       => ['nullable', 'string', 'max:100'],  // ex: "1 fois / 5 ans"
            'color_code'       => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order'       => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'score.unique'     => 'Ce score est déjà utilisé pour cette configuration de matrice.',
            'color_code.regex' => 'La couleur doit être au format hexadécimal (#RRGGBB).',
        ];
    }
}
