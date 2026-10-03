<?php

namespace Tests\Feature\Security;

use App\Models\Item;
use App\Services\ItemPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(ItemPhotoService::disk());
    }

    #[Test]
    public function it_accepts_a_valid_jpeg_photo(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Dompet Berfoto',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'Ada inisial nama di bagian dalam',
            'photo' => UploadedFile::fake()->image('dompet.jpg', 800, 600),
        ])->assertRedirect();

        $item = Item::query()->latest('id')->firstOrFail();

        $this->assertNotNull($item->photo_path);
        $this->assertStringStartsWith('items/', $item->photo_path);
        Storage::disk(ItemPhotoService::disk())->assertExists($item->photo_path);
        $this->assertSame('image/webp', (new \finfo(FILEINFO_MIME_TYPE))->buffer(Storage::disk(ItemPhotoService::disk())->get($item->photo_path)));
    }

    #[Test]
    public function it_rejects_a_file_that_is_not_an_image(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'File Jahat',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'Ciri rahasia yang cukup panjang',
            'photo' => UploadedFile::fake()->create('skrip.php', 100, 'application/x-php'),
        ])->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('items', 0);
    }

    #[Test]
    public function it_rejects_a_disallowed_image_extension(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Gambar BMP',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'Ciri rahasia yang cukup panjang',
            'photo' => UploadedFile::fake()->create('gambar.bmp', 200, 'image/bmp'),
        ])->assertSessionHasErrors('photo');
    }

    #[Test]
    public function it_rejects_a_photo_larger_than_five_megabytes(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Foto Terlalu Besar',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'Ciri rahasia yang cukup panjang',
            'photo' => UploadedFile::fake()->image('besar.jpg')->size(6000),
        ])->assertSessionHasErrors('photo');
    }

    #[Test]
    public function the_stored_filename_is_generated_by_the_server_not_the_client(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Uji Nama File',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'Ciri rahasia yang cukup panjang',
            'photo' => UploadedFile::fake()->image('../../evil;rm -rf.jpg', 400, 400),
        ]);

        $item = Item::query()->latest('id')->firstOrFail();

        $this->assertStringNotContainsString('evil', $item->photo_path);
        $this->assertStringNotContainsString('..', $item->photo_path);
        $this->assertStringNotContainsString('rm -rf', $item->photo_path);
        $this->assertSame(1, preg_match('/^items\/[a-z0-9]+-\d+\.webp$/', $item->photo_path), 'Filename should be a server-generated webp.');
    }

    #[Test]
    public function photos_are_re_encoded_to_webp_which_strips_metadata(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Foto PNG',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'Ciri rahasia yang cukup panjang',
            'photo' => UploadedFile::fake()->image('layar.png', 1200, 900),
        ]);

        $item = Item::query()->latest('id')->firstOrFail();

        // Re-encoding is what drops EXIF/GPS from the original upload.
        $this->assertStringEndsWith('.webp', $item->photo_path);
        Storage::disk(ItemPhotoService::disk())->assertExists($item->photo_path);
    }

    #[Test]
    public function uploads_are_throttled_per_account(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $payload = [
            'category_id' => $category->id,
            'title' => 'Laporan Berulang',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'Ciri rahasia yang cukup panjang',
        ];

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($student)->post(route('items.store-found'), $payload);
        }

        $this->actingAs($student)
            ->post(route('items.store-found'), $payload)
            ->assertStatus(429);
    }
}
