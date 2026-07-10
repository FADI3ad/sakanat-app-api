<?php

namespace App\Http\Requests\Type;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255', 'unique:types,name'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', 'boolean'],
            'icon'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'اسم النوع مطلوب.',
            'name.string'       => 'اسم النوع يجب أن يكون نصاً.',
            'name.max'          => 'اسم النوع لا يجب أن يتجاوز 255 حرفاً.',
            'name.unique'       => 'اسم النوع مستخدم بالفعل.',
            'sort_order.integer'=> 'ترتيب العرض يجب أن يكون رقماً صحيحاً.',
            'sort_order.min'    => 'ترتيب العرض لا يمكن أن يكون بالسالب.',
            'description.string'=> 'الوصف يجب أن يكون نصاً.',
            'status.boolean'    => 'الحالة يجب أن تكون منطقية (true/false).',
            'icon.image'        => 'الأيقونة يجب أن تكون صورة.',
            'icon.mimes'        => 'الأيقونة يجب أن تكون من نوع: jpeg, png, jpg, webp, svg.',
            'icon.max'          => 'حجم الأيقونة يجب ألا يتجاوز 2 ميجابايت.',
        ];
    }
}
