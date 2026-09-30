<?php

namespace App\Http\Requests\Items;

use App\Services\ItemPhotoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFoundItemRequest extends FormRequest
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
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
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
            'verification_answer' => ['required', 'string', 'min:3', 'max:150'],
            'photo' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:'.implode(',', ItemPhotoService::ALLOWED_MIMES),
                'max:'.ItemPhotoService::MAX_KILOBYTES,
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
            'verification_answer.min' => 'Informasi verifikasi minimal 3 karakter.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
