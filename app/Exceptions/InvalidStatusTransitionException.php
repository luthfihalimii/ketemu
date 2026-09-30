<?php

namespace App\Exceptions;

use App\Enums\ItemStatus;
use RuntimeException;

class InvalidStatusTransitionException extends RuntimeException
{
    public function __construct(
        public readonly ItemStatus $from,
        public readonly ItemStatus $to,
    ) {
        parent::__construct(sprintf(
            'Status tidak dapat diubah dari %s ke %s.',
            $from->value,
            $to->value,
        ));
    }

    public static function make(ItemStatus $from, ItemStatus $to): self
    {
        return new self($from, $to);
    }
}
