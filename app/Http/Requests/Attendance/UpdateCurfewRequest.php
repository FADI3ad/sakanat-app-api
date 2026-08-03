<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurfewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // وقت بالتنسيق HH:MM أو HH:MM:SS
            'curfew_time' => ['required', 'date_format:H:i,H:i:s'],
        ];
    }

    public function messages(): array
    {
        return [
            'curfew_time.required'    => 'وقت الكيرفيو مطلوب.',
            'curfew_time.date_format' => 'صيغة الوقت يجب أن تكون HH:MM أو HH:MM:SS (مثال: 23:00).',
        ];
    }
}
