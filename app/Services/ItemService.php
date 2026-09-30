<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Exceptions\InvalidMatchException;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ItemService
{
    public function __construct(
        private readonly ItemPhotoService $photos,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Create a "barang ditemukan" report.
     *
     * The item starts as WAITING_DEPOSIT: it only becomes claimable once the
     * finder confirms the item was handed over to security staff.
     *
     * @param  array<string, mixed>  $data
     */
    public function createFound(User $reporter, array $data, ?UploadedFile $photo = null): Item
    {
        $item = new Item([
            'user_id' => $reporter->id,
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location_id' => $data['location_id'] ?? null,
            'location_detail' => $data['location_detail'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? null,
            'color' => $data['color'] ?? null,
            'brand' => $data['brand'] ?? null,
            'deposit_location_id' => $data['deposit_location_id'] ?? null,
            'deposit_note' => $data['deposit_note'] ?? null,
            'verification_question' => $data['verification_question'] ?? null,
            'verification_answer' => Item::normalizeAnswer($data['verification_answer']),
        ]);

        $item->status = ItemStatus::WaitingDeposit;
        $item->save();

        if ($photo !== null) {
            $item->photo_path = $this->photos->store($photo, $item->id);
            $item->save();
        }

        $this->audit->log(
            event: 'item.found_reported',
            description: 'Laporan barang ditemukan dibuat.',
            auditable: $item,
            properties: ['status' => $item->status->value],
            user: $reporter,
        );

        return $item;
    }

    /**
     * Create a "barang hilang" report.
     *
     * @param  array<string, mixed>  $data
     */
    public function createLost(User $reporter, array $data, ?UploadedFile $photo = null): Item
    {
        $item = new Item([
            'user_id' => $reporter->id,
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location_id' => $data['location_id'] ?? null,
            'location_detail' => $data['location_detail'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? null,
            'color' => $data['color'] ?? null,
            'brand' => $data['brand'] ?? null,
            // Lost reports keep a private marker so the owner can prove ownership later.
            'verification_question' => 'Ciri khusus barang ini?',
            'verification_answer' => Item::normalizeAnswer($data['verification_answer']),
        ]);

        $item->status = ItemStatus::Reported;
        $item->save();

        if ($photo !== null) {
            $item->photo_path = $this->photos->store($photo, $item->id);
            $item->save();
        }

        $this->audit->log(
            event: 'item.lost_reported',
            description: 'Laporan barang hilang dibuat.',
            auditable: $item,
            properties: ['status' => $item->status->value],
            user: $reporter,
        );

        return $item;
    }

    /**
     * Finder confirms the item is now held at the agreed security post.
     */
    public function confirmDeposit(Item $item): Item
    {
        $item->setStatus(ItemStatus::Stored);
        $item->save();

        $this->audit->log(
            event: 'item.deposited',
            description: 'Penemu mengonfirmasi barang telah dititipkan ke satpam.',
            auditable: $item,
            properties: ['status' => $item->status->value],
        );

        return $item;
    }

    /**
     * Found items that could plausibly be the belonging described by a lost
     * report. This is a plain suggestion list, not a decision: the owner still
     * has to claim the item and pass the finder's verification.
     *
     * @return Collection<int, Item>
     */
    public function candidateFoundItems(Item $lost, int $limit = 6): Collection
    {
        return Item::query()
            ->discoverable()
            ->whereKeyNot($lost->getKey())
            ->when($lost->category_id, fn ($query) => $query->where('category_id', $lost->category_id))
            ->with(['category:id,name', 'location:id,name'])
            ->latest('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Record that a lost report and a found item describe the same belonging.
     */
    public function matchLostToFound(Item $lost, Item $found, User $actor): Item
    {
        return DB::transaction(function () use ($lost, $found, $actor) {
            $lost = Item::query()->whereKey($lost->getKey())->lockForUpdate()->firstOrFail();
            $found = Item::query()->whereKey($found->getKey())->lockForUpdate()->firstOrFail();

            $lost->matchTo($found);

            $this->audit->log(
                event: 'item.matched',
                description: 'Laporan barang hilang dicocokkan dengan barang temuan.',
                auditable: $lost,
                properties: [
                    'lost_report_id' => $lost->id,
                    'found_item_id' => $found->id,
                ],
                user: $actor,
            );

            return $lost;
        });
    }

    /**
     * Undo a match. Only allowed while the report is still open.
     *
     * @throws InvalidMatchException
     */
    public function unmatchLost(Item $lost, User $actor): Item
    {
        return DB::transaction(function () use ($lost, $actor) {
            $lost = Item::query()->whereKey($lost->getKey())->lockForUpdate()->firstOrFail();

            // Once the owner has the item back, the link is part of the history
            // and must not be erased.
            if ($lost->status !== ItemStatus::Reported) {
                throw InvalidMatchException::reportResolved();
            }

            $lost->unmatch();

            $this->audit->log(
                event: 'item.unmatched',
                description: 'Kecocokan laporan barang hilang dibatalkan.',
                auditable: $lost,
                properties: ['lost_report_id' => $lost->id],
                user: $actor,
            );

            return $lost;
        });
    }
}
