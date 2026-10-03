<?php

namespace App\Http\Requests\Items;

use App\Services\ItemPhotoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLostItemRequest extends FormRequest
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
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'color' => ['nullable', 'string', 'max:40'],
            'brand' => ['nullable', 'string', 'max:60'],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('is_active', true)],
            'location_detail' => ['nullable', 'string', 'max:120'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'photo' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:'.implode(',', ItemPhotoService::ALLOWED_MIMES),
                'max:'.ItemPhotoService::MAX_KILOBYTES,
                'dimensions:max_width=4000,max_height=4000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori barang wajib dipilih.',
            'title.required' => 'Nama barang wajib diisi.',
            'description.required' => 'Deskripsi barang wajib diisi.',
            'description.min' => 'Deskripsi minimal 10 karakter.',
            'location_id.required' => 'Perkiraan lokasi kehilangan wajib dipilih.',
            'occurred_at.required' => 'Perkiraan waktu kehilangan wajib diisi.',
            'occurred_at.before_or_equal' => 'Waktu kehilangan tidak boleh di masa depan.',
            'photo.dimensions' => 'Dimensi foto maksimal 4000 × 4000 piksel.',
            'photo.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
