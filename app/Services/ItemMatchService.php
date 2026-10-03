<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Database\Eloquent\Collection;

/**
 * AI Matching P2 — heuristik lokal, tanpa LLM / API eksternal.
 *
 * Sesuai AGENTS.md §19: ini hanya daftar saran berperingkat, BUKAN penentu
 * kepemilikan. Pengambilan tetap wajib lewat klaim + jawaban verifikasi
 * penemu + kode pickup + verifikasi satpam.
 *
 * Skor 0–100: teks 50 + kategori 25 + lokasi 15 + kedekatan tanggal 10.
 * Upgrade path ke embedding (pgvector/Meilisearch/OpenAI) cukup ganti
 * textSimilarity() — kontrak suggestFor() tidak berubah.
 */
class ItemMatchService
{
    /**
     * Kata umum Indonesia yang tidak membedakan barang.
     *
     * @var array<int, string>
     */
    private const STOPWORDS = [
        'yang', 'dan', 'atau', 'dengan', 'untuk', 'dari', 'dalam', 'pada', 'adalah',
        'itu', 'ini', 'saya', 'kami', 'kamu', 'milik', 'barang', 'sebuah', 'satu',
        'dekat', 'lantai', 'gedung', 'ruang', 'kelas', 'kantin', 'parkir', 'pos',
        'telah', 'sudah', 'ada', 'buah', 'warna', 'merek', 'contoh', 'sekitar',
        'the', 'and', 'with', 'from',
    ];

    /**
     * @return Collection<int, Item>
     */
    public function suggestFor(Item $lost, int $limit = 6): Collection
    {
        $candidates = Item::query()
            ->discoverable()
            ->whereKeyNot($lost->getKey())
            ->with(['category:id,name', 'location:id,name'])
            ->latest('occurred_at')
            ->limit(60)
            ->get();

        $lostVector = $this->termVector($this->publicText($lost));

        foreach ($candidates as $candidate) {
            $text = $this->cosine($lostVector, $this->termVector($this->publicText($candidate)));
            $score = (int) round($text * 50);
            $reasons = [];

            if ($text >= 0.15) {
                $reasons[] = 'Deskripsi mirip '.(int) round($text * 100).'%';
            }

            if ($lost->category_id !== null && $candidate->category_id === $lost->category_id) {
                $score += 25;
                $reasons[] = 'Kategori sama';
            }

            if ($lost->location_id !== null && $candidate->location_id === $lost->location_id) {
                $score += 15;
                $reasons[] = 'Lokasi sama';
            }

            if ($lost->occurred_at !== null && $candidate->occurred_at !== null) {
                $days = abs($lost->occurred_at->diffInDays($candidate->occurred_at));
                if ($days <= 7) {
                    $score += 10;
                    $reasons[] = "Waktu berdekatan ({$days} hari)";
                } elseif ($days <= 30) {
                    $score += 5;
                    $reasons[] = "Waktu cukup dekat ({$days} hari)";
                }
            }

            $candidate->setAttribute('match_score', min(100, $score));
            $candidate->setAttribute('match_reasons', $reasons);
        }

        return $candidates
            ->sortByDesc(fn (Item $item) => $item->getAttribute('match_score'))
            ->values()
            ->take($limit);
    }

    /**
     * Hanya kolom publik. verification_answer + private_note TIDAK PERNAH
     * dipakai agar pemilik laporan hilang tidak bisa "meluluskan diri".
     */
    public function publicText(Item $item): string
    {
        return implode(' ', array_filter([
            $item->title,
            $item->description,
            $item->color,
            $item->brand,
        ]));
    }

    /**
     * @return array<string, int>
     */
    public function termVector(string $text): array
    {
        $text = mb_strtolower($text);
        $tokens = preg_split('/[^a-z0-9]+/u', $text) ?: [];
        $vector = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            if (mb_strlen($token) < 3 || in_array($token, self::STOPWORDS, true)) {
                continue;
            }
            $vector[$token] = ($vector[$token] ?? 0) + 1;
        }

        return $vector;
    }

    /**
     * @param  array<string, int>  $a
     * @param  array<string, int>  $b
     */
    public function cosine(array $a, array $b): float
    {
        if ($a === [] || $b === []) {
            return 0.0;
        }

        $dot = 0;
        foreach ($a as $term => $count) {
            $dot += $count * ($b[$term] ?? 0);
        }

        $norm = fn (array $v): float => sqrt(array_sum(array_map(fn (int $c) => $c * $c, $v)));

        $denominator = $norm($a) * $norm($b);

        return $denominator > 0 ? $dot / $denominator : 0.0;
    }
}
