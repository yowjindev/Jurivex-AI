<?php
namespace App\Modules\Documents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    public function rules(): array
    {
        return [
            'title'    => ['sometimes', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'tags'     => ['nullable', 'array'],
            'tags.*'   => ['string', 'max:100'],
        ];
    }

    protected function failedAuthorization(): never
    {
        abort(403, 'Only admins and managers can update documents.');
    }
}
