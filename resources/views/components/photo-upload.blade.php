<div class="border-t border-slate-200 pt-6">
    <label for="photo" class="label">Foto barang (opsional)</label>
    <p id="photo-hint" class="help mb-3">JPG, PNG, atau WEBP; maksimal 5 MB dan 4000 × 4000 piksel. Metadata lokasi dihapus otomatis. Jangan tampilkan ciri rahasia.</p>
    <input id="photo" data-photo-upload type="file" name="photo" accept="image/jpeg,image/png,image/webp" aria-describedby="photo-hint photo-feedback" @if ($errors->has('photo')) aria-invalid="true" @endif class="field file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700">
    <p id="photo-feedback" role="alert" class="mt-2 text-sm text-rose-700">{{ $errors->first('photo') }}</p>
    <img id="photo-preview" hidden alt="Pratinjau foto yang akan diunggah" class="mt-3 max-h-64 rounded-xl object-contain" width="320" height="240">
</div>
