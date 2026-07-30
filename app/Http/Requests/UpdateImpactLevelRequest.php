<?php

namespace App\Http\Requests;

use App\Models\RiskImpactLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImpactLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Route model binding est désactivé → résolution manuelle
        $levelId  = (int) $this->route('impact_level');
        $level    = RiskImpactLevel::findOrFail($levelId);
        $configId = $level->matrix_config_id;

        return [
            'label'       => ['required', 'string', 'max:100'],
            'score'       => [
                'required',
                'integer',
                'min:1',
                'max:10',
                Rule::unique('risk_impact_levels', 'score')
                    ->where('matrix_config_id', $configId)
                    ->whereNull('deleted_at')
                    ->ignore($levelId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
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
