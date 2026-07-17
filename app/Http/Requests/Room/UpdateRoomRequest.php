<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'        => 'اسم الغرفة يجب أن يكون نصاً.',
            'name.max'           => 'اسم الغرفة لا يتجاوز 255 حرفاً.',
            'description.string' => 'وصف الغرفة يجب أن يكون نصاً.',
        ];
    }
}
