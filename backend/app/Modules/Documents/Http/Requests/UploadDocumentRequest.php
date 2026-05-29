<?php
namespace App\Modules\Documents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file'     => ['required', 'file', 'mimes:pdf,docx,doc,txt', 'max:204800'],
            'category' => ['nullable', 'string', 'max:255'],
        ];
    }
}
