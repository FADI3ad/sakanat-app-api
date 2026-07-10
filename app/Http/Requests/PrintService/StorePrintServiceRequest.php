<?php

namespace App\Http\Requests\PrintService;

use Illuminate\Foundation\Http\FormRequest;

class StorePrintServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'                          => ['required', 'string', 'max:255'],
            'description'                    => ['nullable', 'string'],
            'image'                          => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'delevery_available'             => ['required', 'boolean'],
            'has_color_option'               => ['required', 'boolean'],
            'black_and_white_price_per_page' => ['required', 'numeric', 'min:0'],
            'color_price_per_page'           => ['required_if:has_color_option,true', 'nullable', 'numeric', 'min:0'],
            'area_id'                        => ['required', 'integer', 'exists:areas,id'],
        ];
    }

    /**
     * Get the validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required'                          => 'عنوان الخدمة مطلوب.',
            'title.string'                            => 'عنوان الخدمة يجب أن يكون نصاً.',
            'title.max'                               => 'عنوان الخدمة يجب ألا يتجاوز 255 حرفاً.',
            'description.string'                      => 'الوصف يجب أن يكون نصاً.',
            'image.image'                             => 'الملف المرفوع يجب أن يكون صورة.',
            'image.mimes'                             => 'الصورة يجب أن تكون من نوع: jpeg, png, jpg, webp.',
            'image.max'                               => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
            'delevery_available.required'             => 'تحديد توفر خدمة التوصيل مطلوب.',
            'delevery_available.boolean'              => 'قيمة خدمة التوصيل غير صالحة.',
            'has_color_option.required'               => 'تحديد توفر خيار الطباعة الملونة مطلوب.',
            'has_color_option.boolean'                => 'قيمة خيار الطباعة الملونة غير صالحة.',
            'black_and_white_price_per_page.required' => 'سعر الطباعة باللون الأسود والأبيض للصفحة مطلوب.',
            'black_and_white_price_per_page.numeric'  => 'السعر يجب أن يكون رقماً.',
            'black_and_white_price_per_page.min'      => 'السعر لا يمكن أن يكون بالسالب.',
            'color_price_per_page.required_if'        => 'سعر الطباعة الملونة مطلوب عند تفعيل خيار الطباعة الملونة.',
            'color_price_per_page.numeric'            => 'السعر يجب أن يكون رقماً.',
            'color_price_per_page.min'                => 'السعر لا يمكن أن يكون بالسالب.',
            'area_id.required'                        => 'المنطقة مطلوبة.',
            'area_id.integer'                         => 'معرف المنطقة غير صالح.',
            'area_id.exists'                          => 'المنطقة المحددة غير موجودة.',
        ];
    }
}
