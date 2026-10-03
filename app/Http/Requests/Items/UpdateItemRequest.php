<?php

namespace App\Http\Requests\Items;

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Services\ItemPhotoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $this->user() !== null && $item !== null
            && ($this->user()->id === $item->user_id || $this->user()->isAdmin());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Item|null $item */
        $item = $this->route('item');
        $isFound = $item !== null && $item->isFoundReport();
        $isEditable = $item !== null && in_array($item->status, [ItemStatus::WaitingDeposit, ItemStatus::Reported], true);

        return [
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => [$isFound ? 'nullable' : 'required', 'string', $isFound ? 'max:1000' : 'min:10', 'max:1000'],
            'private_note' => [$isFound ? 'nullable' : 'prohibited', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:40'],
            'brand' => ['nullable', 'string', 'max:60'],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('is_active', true)],
            'location_detail' => ['nullable', 'string', 'max:120'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            // Lokasi penitipan hanya untuk laporan temuan dan hanya selama
            // belum diserahkan (WAITING_DEPOSIT) agar tidak mengubah pos seenaknya.
            'deposit_location_id' => [
                $isFound && $isEditable ? 'required' : 'prohibited',
                Rule::exists('locations', 'id')->where('is_active', true)->where('type', 'security_post'),
            ],
            'deposit_note' => [$isFound ? 'nullable' : 'prohibited', 'string', 'max:255'],
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
            'title.required' => 'Nama barang wajib diisi.',
            'description.required' => 'Deskripsi barang wajib diisi.',
            'location_id.required' => 'Lokasi wajib dipilih.',
            'occurred_at.before_or_equal' => 'Waktu tidak boleh di masa depan.',
            'deposit_location_id.required' => 'Lokasi penitipan wajib dipilih.',
            'deposit_location_id.prohibited' => 'Lokasi penitipan tidak dapat diubah setelah barang dititipkan. Hubungi admin bila salah pos.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
