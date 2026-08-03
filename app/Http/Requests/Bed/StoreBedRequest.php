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
            'student_phone' => ['nullable', 'string'],
            'phone'         => ['nullable', 'string'],
            'user_id'       => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'occupant_name.string' => 'اسم الساكن يجب أن يكون نصاً.',
            'occupant_name.max'    => 'اسم الساكن لا يتجاوز 255 حرفاً.',
            'student_phone.string' => 'رقم تليفون الطالب يجب أن يكون نصاً.',
            'phone.string'         => 'رقم تليفون الطالب يجب أن يكون نصاً.',
            'user_id.exists'       => 'المستخدم المحدد غير موجود.',
        ];
    }
}
