<?php

namespace App\Http\Requests\Items;

use App\Models\Item;
use App\Services\ItemPhotoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFoundItemRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('verification_answer'))) {
            $this->merge(['verification_answer' => Item::normalizeAnswer($this->input('verification_answer'))]);
        }
    }

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
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            // Catatan internal: tidak tampil ke publik, hanya pelapor/satpam/admin.
            'private_note' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:40'],
            'brand' => ['nullable', 'string', 'max:60'],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('is_active', true)],
            'location_detail' => ['nullable', 'string', 'max:120'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'deposit_location_id' => [
                'required',
                Rule::exists('locations', 'id')->where('is_active', true)->where('type', 'security_post'),
            ],
            'deposit_note' => ['nullable', 'string', 'max:255'],
            'verification_question' => ['nullable', 'string', 'max:150'],
            'verification_answer' => [
                'required',
                'string',
                'min:8',
                'max:150',
                // Cegah jawaban bocor verbatim di kolom publik.
                function (string $attribute, mixed $value, \Closure $fail) {
                    $answer = mb_strtolower(trim((string) $value));
                    if ($answer === '') {
                        return;
                    }
                    $public = mb_strtolower(trim((string) $this->input('title').' '.(string) $this->input('description').' '.(string) $this->input('color').' '.(string) $this->input('brand')));
                    if ($public !== '' && mb_strpos($public, $answer) !== false) {
                        $fail('Jawaban verifikasi tidak boleh sama persis dengan nama/deskripsi publik. Gunakan ciri yang tidak terlihat di foto atau deskripsi.');
                    }
                },
            ],
            'photo' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:'.implode(',', ItemPhotoService::ALLOWED_MIMES),
                'max:'.ItemPhotoService::MAX_KILOBYTES,
                'dimensions:max_width=4000,max_height=4000',
            ],
            'confirm_deposit' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori barang wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid.',
            'title.required' => 'Nama barang wajib diisi.',
            'title.min' => 'Nama barang minimal 3 karakter.',
            'location_id.required' => 'Lokasi ditemukan wajib dipilih.',
            'occurred_at.required' => 'Waktu ditemukan wajib diisi.',
            'occurred_at.before_or_equal' => 'Waktu ditemukan tidak boleh di masa depan.',
            'deposit_location_id.required' => 'Lokasi penitipan wajib dipilih.',
            'deposit_location_id.exists' => 'Lokasi penitipan harus berupa pos satpam yang aktif.',
            'verification_answer.required' => 'Informasi verifikasi kepemilikan wajib diisi.',
            'verification_answer.min' => 'Informasi verifikasi minimal 8 karakter. Gunakan ciri khusus yang tidak terlihat pada foto publik.',
            'photo.dimensions' => 'Dimensi foto maksimal 4000 × 4000 piksel.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
