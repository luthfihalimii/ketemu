<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class PickupQrService
{
    /**
     * QR payload = kode plaintext itu sendiri (mis. ABCD-1234).
     * Satpam scan → input kode terisi otomatis, tanpa mengetik.
     * QR sama sensitifnya dengan kode: jangan di-screenshot ke orang lain.
     */
    public function svgDataUri(string $plainCode): string
    {
        $qrCode = new QrCode(data: trim($plainCode), size: 240, margin: 8);

        $svg = (new SvgWriter)->write($qrCode)->getString();

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
