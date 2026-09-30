<?php

namespace App\Http\Requests\Claims;

use Illuminate\Foundation\Http\FormRequest;

class SubmitClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // The answer is never echoed back and is not a guessable option list.
            'answer' => ['required', 'string', 'min:3', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answer.required' => 'Jawaban verifikasi wajib diisi.',
            'answer.min' => 'Jawaban verifikasi minimal 3 karakter.',
        ];
    }
}
