<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Exceptions\InvalidMatchException;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

class ItemService
{
    public function __construct(
        private readonly ItemPhotoService $photos,
        private readonly AuditLogger $audit,
        private readonly ItemMatchService $matcher,
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
        $path = null;
        try {
            return DB::transaction(function () use ($reporter, $data, $photo, &$path) {
                $item = new Item([
                    'user_id' => $reporter->id,
                    'category_id' => $data['category_id'] ?? null,
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'private_note' => $data['private_note'] ?? null,
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
                    $path = $this->photos->store($photo, $item->id);
                    $item->photo_path = $path;
                    $item->save();
                }

                $this->audit->log(
                    event: 'item.found_reported',
                    description: 'Laporan barang ditemukan dibuat.',
                    auditable: $item,
                    properties: ['status' => $item->status->value],
                    user: $reporter,
                );

                if ($data['confirm_deposit'] ?? false) {
                    $item = $this->confirmDeposit($item);
                }

                return $item;
            });
        } catch (Throwable $exception) {
            $this->photos->delete($path);
            throw $exception;
        }
    }

    /**
     * Create a "barang hilang" report.
     *
     * @param  array<string, mixed>  $data
     */
    public function createLost(User $reporter, array $data, ?UploadedFile $photo = null): Item
    {
        $path = null;
        try {
            return DB::transaction(function () use ($reporter, $data, $photo, &$path) {
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
                ]);

                $item->status = ItemStatus::Reported;
                $item->save();

                if ($photo !== null) {
                    $path = $this->photos->store($photo, $item->id);
                    $item->photo_path = $path;
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
            });
        } catch (Throwable $exception) {
            $this->photos->delete($path);
            throw $exception;
        }
    }

    /**
     * Update a report before it leaves the editable window.
     *
     * Hanya WAITING_DEPOSIT / REPORTED yang bisa diubah pemilik; setelah
     * STORED, perubahan dialihkan ke admin agar klaim berjalan tidak rusak
     * (terutama jawaban verifikasi yang immutable).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateReport(Item $item, array $data, ?UploadedFile $photo = null): Item
    {
        $newPath = null;
        $oldPath = $item->photo_path;
        try {
            return DB::transaction(function () use ($item, $data, $photo, &$newPath, $oldPath) {
                $locked = Item::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
                if (! in_array($locked->status, [ItemStatus::WaitingDeposit, ItemStatus::Reported], true)) {
                    throw ValidationException::withMessages(['item' => 'Laporan sudah tidak dapat diubah. Hubungi admin bila ada kesalahan data.']);
                }

                $locked->fill([
                    'category_id' => $data['category_id'] ?? $locked->category_id,
                    'title' => $data['title'] ?? $locked->title,
                    'description' => $data['description'] ?? $locked->description,
                    'private_note' => $locked->isFoundReport() ? ($data['private_note'] ?? $locked->private_note) : $locked->private_note,
                    'location_id' => $data['location_id'] ?? $locked->location_id,
                    'location_detail' => $data['location_detail'] ?? $locked->location_detail,
                    'occurred_at' => $data['occurred_at'] ?? $locked->occurred_at,
                    'color' => $data['color'] ?? $locked->color,
                    'brand' => $data['brand'] ?? $locked->brand,
                    'deposit_location_id' => $locked->isFoundReport() ? ($data['deposit_location_id'] ?? $locked->deposit_location_id) : $locked->deposit_location_id,
                    'deposit_note' => $locked->isFoundReport() ? ($data['deposit_note'] ?? $locked->deposit_note) : $locked->deposit_note,
                ]);
                $locked->save();

                if ($photo !== null) {
                    $newPath = $this->photos->store($photo, $locked->id);
                    $locked->photo_path = $newPath;
                    $locked->save();
                    $this->photos->delete($oldPath);
                }

                $this->audit->log(
                    event: 'item.updated',
                    description: 'Laporan diperbarui pemilik.',
                    auditable: $locked,
                    properties: ['status' => $locked->status->value],
                );

                return $locked;
            });
        } catch (Throwable $exception) {
            $this->photos->delete($newPath);
            throw $exception;
        }
    }

    /**
     * Finder confirms the item is now held at the agreed security post.
     *
     * P0 2-pihak: atestasi penemu saja tidak cukup. Metode ini mencatat
     * permintaan penitipan; satpam wajib memverifikasi fisik via
     * confirmDepositByGuard() sebelum badge "terkonfirmasi satpam" muncul.
     * Status tetap STORED agar alur klaim lama tidak macet, tapi admin/guard
     * dapat melihat mana yang belum dikonfirmasi.
     */
    public function confirmDeposit(Item $item): Item
    {
        return DB::transaction(function () use ($item) {
            $item = Item::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            if (! $item->isFoundReport() || $item->status !== ItemStatus::WaitingDeposit) {
                throw ValidationException::withMessages(['deposit' => 'Barang ini sudah tidak dapat dikonfirmasi penitipannya.']);
            }
            $item->setStatus(ItemStatus::Stored);
            $item->deposit_requested_at ??= now();
            $item->save();

            $this->audit->log(
                event: 'item.deposited',
                description: 'Penemu mengonfirmasi barang telah dititipkan ke satpam. Menunggu verifikasi fisik satpam.',
                auditable: $item,
                properties: ['status' => $item->status->value],
            );

            return $item;
        });
    }

    /**
     * Satpam memverifikasi barang fisik sudah ada di pos.
     */
    public function confirmDepositByGuard(Item $item, User $guard): Item
    {
        return DB::transaction(function () use ($item, $guard) {
            $item = Item::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            if (! $item->isFoundReport()) {
                throw ValidationException::withMessages(['deposit' => 'Hanya laporan temuan yang dapat dikonfirmasi satpam.']);
            }
            $item->deposit_confirmed_at = now();
            $item->deposit_confirmed_by = $guard->id;
            $item->save();

            $this->audit->log(
                event: 'item.deposit_confirmed',
                description: 'Satpam memverifikasi barang fisik sudah ada di pos.',
                auditable: $item,
                properties: ['status' => $item->status->value],
                user: $guard,
            );

            return $item;
        });
    }

    /**
     * Found items that could plausibly be the belonging described by a lost
     * report. Didelegasikan ke ItemMatchService (P2 AI heuristik lokal):
     * skor teks + kategori + lokasi + tanggal. Bukan keputusan — pemilik
     * tetap harus klaim + lulus verifikasi penemu.
     *
     * @return Collection<int, Item>
     */
    public function candidateFoundItems(Item $lost, int $limit = 6): Collection
    {
        return $this->matcher->suggestFor($lost, $limit);
    }

    /**
     * Record that a lost report and a found item describe the same belonging.
     */
    public function matchLostToFound(Item $lost, Item $found, User $actor): Item
    {
        return DB::transaction(function () use ($lost, $found, $actor) {
            $lost = Item::query()->whereKey($lost->getKey())->lockForUpdate()->firstOrFail();
            $found = Item::query()->whereKey($found->getKey())->lockForUpdate()->firstOrFail();

            Gate::forUser($actor)->authorize('update', $lost);
            Gate::forUser($actor)->authorize('view', $found);
            if ($lost->status !== ItemStatus::Reported || ! $found->isPubliclyAvailable()) {
                throw InvalidMatchException::reportResolved();
            }

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
