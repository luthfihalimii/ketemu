<?php

namespace App\Services;

use App\Enums\PickupCodeStatus;
use App\Models\Claim;
use App\Models\PickupCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PickupCodeService
{
    /**
     * Minutes a freshly issued pickup code stays valid.
     */
    public static function validityMinutes(): int
    {
        return (int) config('ketemupens.pickup_code.validity_minutes');
    }

    /**
     * Context string mixed into the HMAC so the pickup-code fingerprint can
     * never collide with another use of the application key.
     */
    private const HASH_CONTEXT = 'ketemupens:pickup-code:';

    /**
     * Issue a single-use pickup code for an approved claim.
     *
     * Returns the plaintext code once; only a hash is persisted, so the caller
     * must surface it to the owner immediately.
     *
     * @return array{code: PickupCode, plain: string}
     */
    public function issue(Claim $claim): array
    {
        $plain = $this->generatePlainCode();

        $pickupCode = DB::transaction(function () use ($claim, $plain) {
            // A claim only ever owns one active code.
            $claim->pickupCode()->where('status', PickupCodeStatus::Active->value)->update([
                'status' => PickupCodeStatus::Cancelled->value,
            ]);

            return PickupCode::create([
                'claim_id' => $claim->id,
                'item_id' => $claim->item_id,
                'user_id' => $claim->user_id,
                'code_hash' => $this->hash($plain),
                // Encrypted (not plaintext) so the owner can view it again.
                'code_encrypted' => $plain,
                'code_hint' => Str::upper(Str::substr($plain, -4)),
                'status' => PickupCodeStatus::Active,
                'expires_at' => now()->addMinutes(self::validityMinutes()),
            ]);
        });

        return ['code' => $pickupCode, 'plain' => $plain];
    }

    /**
     * Resolve an active code from a student-supplied value.
     */
    public function findActiveByPlainText(string $plain): ?PickupCode
    {
        $pickupCode = PickupCode::query()
            ->where('code_hash', $this->hash($this->normalize($plain)))
            ->first();

        if ($pickupCode === null) {
            return null;
        }

        return $pickupCode->isUsable() ? $pickupCode : null;
    }

    public function generatePlainCode(): string
    {
        // Grouped for readability when read aloud to security staff.
        return sprintf(
            '%s-%s',
            Str::upper(Str::random(4)),
            Str::upper(Str::random(4)),
        );
    }

    public function normalize(string $plain): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $plain) ?? '');
    }

    /**
     * Fingerprint a plaintext code for storage and lookup.
     *
     * The context prefix domain-separates this HMAC from any other use of the
     * application key. ponytail: still one shared secret; move to a dedicated
     * PICKUP_CODE_KEY if the app key ever has to be rotated without stranding
     * live pickup codes (a dedicated key would also need a re-hash migration).
     */
    private function hash(string $plain): string
    {
        return hash_hmac('sha256', self::HASH_CONTEXT.$this->normalize($plain), config('app.key'));
    }
}
