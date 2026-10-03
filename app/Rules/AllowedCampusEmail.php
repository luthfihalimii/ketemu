<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Only PENS campus addresses may self-register: student sub-domains
 * (*.student.pens.ac.id) and the main staff domain (@pens.ac.id).
 *
 * The patterns live in config/ketemupens.php so the campus can widen or
 * narrow the policy without a code change.
 */
class AllowedCampusEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Email tidak valid.');

            return;
        }

        $patterns = (array) config('ketemupens.auth.allowed_email_patterns', []);

        foreach ($patterns as $pattern) {
            if (is_string($pattern) && preg_match($pattern, $value) === 1) {
                return;
            }
        }

        $fail('Pendaftaran hanya untuk email kampus PENS (mis. nama@student.pens.ac.id atau nama@pens.ac.id).');
    }
}
