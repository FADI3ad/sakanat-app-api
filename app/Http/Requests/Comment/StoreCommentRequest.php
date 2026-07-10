<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth check is handled by middleware
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'محتوى التعليق مطلوب.',
            'body.min'      => 'التعليق يجب أن يكون 3 أحرف على الأقل.',
            'body.max'      => 'التعليق لا يمكن أن يتجاوز 1000 حرف.',
        ];
    }
}
