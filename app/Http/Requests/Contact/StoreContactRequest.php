<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Any authenticated user can send a contact message
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:3'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'الموضوع مطلوب.',
            'subject.max'      => 'الموضوع لا يمكن أن يتجاوز 255 حرف.',
            'message.required' => 'محتوى الرسالة مطلوب.',
            'message.min'      => 'الرسالة يجب أن تكون 10 أحرف على الأقل.',
        ];
    }
}
