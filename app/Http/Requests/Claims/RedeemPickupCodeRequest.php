<?php

namespace App\Http\Requests\Claims;

use Illuminate\Foundation\Http\FormRequest;

class RedeemPickupCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canVerifyPickup() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Formatting is normalised in PickupCodeService, so guards may
            // paste the code in any case or grouping.
            'code' => ['required', 'string', 'max:20'],

            // The guard must record the identity document they physically
            // checked. A bare code can be forwarded to someone else, so the
            // handover is only traceable if the recipient is named.
            'recipient_id_number' => ['required', 'string', 'min:4', 'max:40'],
            'recipient_name' => ['required', 'string', 'min:3', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode pengambilan wajib diisi.',
            'recipient_id_number.required' => 'Nomor identitas (KTM/KTP) wajib dicatat.',
            'recipient_id_number.min' => 'Nomor identitas minimal 4 karakter.',
            'recipient_name.required' => 'Nama penerima wajib dicatat.',
            'recipient_name.min' => 'Nama penerima minimal 3 karakter.',
        ];
    }
}
