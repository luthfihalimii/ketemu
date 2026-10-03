<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Record an important activity for traceability.
     *
     * Never pass secrets, tokens, or verification answers in $properties.
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $event,
        string $description,
        ?Model $auditable = null,
        array $properties = [],
        ?User $user = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->id ?? auth()->id(), // @phpstan-ignore nullsafe.neverNull (mixed; jelas bisa null)
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => request()->ip(),
        ]);
    }
}
