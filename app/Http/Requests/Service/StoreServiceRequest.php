<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'              => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'image'              => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_available'       => ['nullable', 'boolean'],
            'delevery_available' => ['required', 'boolean'],
            'price'              => ['required', 'numeric', 'min:0'],
            'area_id'            => ['required', 'integer', 'exists:areas,id'],
            'type_id'            => ['required', 'integer', 'exists:types,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'              => 'عنوان الخدمة مطلوب.',
            'title.string'                => 'عنوان الخدمة يجب أن يكون نصاً.',
            'title.max'                   => 'عنوان الخدمة لا يجب أن يتجاوز 255 حرفاً.',
            'description.string'          => 'الوصف يجب أن يكون نصاً.',
            'image.image'                 => 'الملف المرفوع يجب أن يكون صورة.',
            'image.mimes'                 => 'الصورة يجب أن تكون من نوع: jpeg, png, jpg, webp.',
            'image.max'                   => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
            'is_available.boolean'        => 'حالة التوفر يجب أن تكون منطقية.',
            'delevery_available.required' => 'تحديد توفر خدمة التوصيل مطلوب.',
            'delevery_available.boolean'  => 'قيمة خدمة التوصيل يجب أن تكون منطقية.',
            'price.required'              => 'سعر الخدمة مطلوب.',
            'price.numeric'               => 'سعر الخدمة يجب أن يكون رقماً.',
            'price.min'                   => 'سعر الخدمة لا يمكن أن يكون بالسالب.',
            'area_id.required'            => 'المنطقة مطلوبة.',
            'area_id.integer'             => 'معرّف المنطقة يجب أن يكون رقماً صحيحاً.',
            'area_id.exists'              => 'المنطقة المحددة غير موجودة.',
            'type_id.required'            => 'نوع الخدمة مطلوب.',
            'type_id.integer'             => 'معرّف النوع يجب أن يكون رقماً صحيحاً.',
            'type_id.exists'              => 'نوع الخدمة المحدد غير موجود.',
        ];
    }
}
