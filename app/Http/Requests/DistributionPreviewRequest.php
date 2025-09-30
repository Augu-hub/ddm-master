<?php
// app/Http/Requests/DistributionPreviewRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistributionPreviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'entity_id' => ['required','integer','exists:entities,id'],
            'nodes'     => ['required','array','min:1'],
            'nodes.*.id'   => ['required','integer'],
            'nodes.*.type' => ['required','in:macro,process,activity'],
        ];
    }
}
