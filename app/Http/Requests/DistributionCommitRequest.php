<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistributionCommitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'entity_id'      => ['required','integer','exists:entities,id'],
            'activity_ids'   => ['required','array','min:1'],
            'activity_ids.*' => ['integer','exists:activities,id'],
            'replace'        => ['sometimes','boolean'],
        ];
    }
}
