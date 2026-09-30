<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a report is paired with something it cannot be paired with,
 * for example matching a found item to another found item.
 */
class InvalidMatchException extends RuntimeException
{
    public static function notALostReport(): self
    {
        return new self('Hanya laporan barang hilang yang dapat dicocokkan.');
    }

    public static function notAFoundReport(): self
    {
        return new self('Hanya laporan barang temuan yang dapat dijadikan pasangan.');
    }

    public static function selfMatch(): self
    {
        return new self('Laporan tidak dapat dicocokkan dengan dirinya sendiri.');
    }

    public static function reportResolved(): self
    {
        return new self('Laporan yang sudah selesai tidak dapat dibatalkan kecocokannya.');
    }
}
