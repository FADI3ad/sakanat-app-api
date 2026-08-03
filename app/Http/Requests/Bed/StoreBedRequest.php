<?php

namespace App\Http\Requests\Bed;

use Illuminate\Foundation\Http\FormRequest;

class StoreBedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'occupant_name' => ['nullable', 'string', 'max:255'],
            'user_id'       => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'occupant_name.string' => 'اسم الساكن يجب أن يكون نصاً.',
            'occupant_name.max'    => 'اسم الساكن لا يتجاوز 255 حرفاً.',
            'user_id.exists'       => 'المستخدم المحدد غير موجود.',
        ];
    }
}
