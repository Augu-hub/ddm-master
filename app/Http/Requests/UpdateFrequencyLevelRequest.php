<?php

namespace App\Http\Requests;

use App\Models\RiskFrequencyLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFrequencyLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Route model binding est désactivé → résolution manuelle
        $levelId  = (int) $this->route('frequency_level');
        $level    = RiskFrequencyLevel::findOrFail($levelId);
        $configId = $level->matrix_config_id;

        return [
            'label'       => ['required', 'string', 'max:100'],
            'score'       => [
                'required',
                'integer',
                'min:1',
                'max:10',
                Rule::unique('risk_frequency_levels', 'score')
                    ->where('matrix_config_id', $configId)
                    ->whereNull('deleted_at')
                    ->ignore($levelId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'recurrence'  => ['nullable', 'string', 'max:100'],
            'color_code'  => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order'  => ['required', 'integer', 'min:0'],
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
