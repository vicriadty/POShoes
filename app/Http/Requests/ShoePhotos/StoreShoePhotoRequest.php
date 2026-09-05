<?php

namespace App\Http\Requests\ShoePhotos;

use App\Domain\ShoePhotos\Actions\UploadShoePhoto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShoePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('work.photos') ?? false;
    }

    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120', // 5 MB
            ],
            'type' => [
                'required',
                Rule::in(UploadShoePhoto::ALLOWED_TYPES),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format harus jpeg/png/webp.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
            'type.in' => 'Tipe foto harus before/during/after.',
        ];
    }
}
