<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'           => ['sometimes', 'string', 'max:255'],
            'city'            => ['sometimes', 'string', 'max:100'],
            'floor'           => ['sometimes', 'nullable', 'string', 'max:20'],
            'address_details' => ['sometimes', 'nullable', 'string'],
            'is_available'    => ['sometimes', 'boolean'],
            'description'     => ['sometimes', 'nullable', 'string'],
            'latitude'        => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude'       => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'radius'          => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.string'            => 'اسم السكن يجب أن يكون نصاً.',
            'title.max'               => 'اسم السكن لا يتجاوز 255 حرفاً.',
            'city.string'             => 'اسم المدينة يجب أن يكون نصاً.',
            'city.max'                => 'اسم المدينة لا يتجاوز 100 حرف.',
            'floor.string'            => 'رقم الطابق يجب أن يكون نصاً.',
            'address_details.string'  => 'تفاصيل العنوان يجب أن تكون نصاً.',
            'is_available.boolean'    => 'حالة التوفر يجب أن تكون منطقية.',
            'description.string'      => 'الوصف يجب أن يكون نصاً.',
            'latitude.numeric'        => 'خط العرض يجب أن يكون رقماً.',
            'latitude.between'        => 'خط العرض يجب أن يكون بين -90 و 90 درجة.',
            'longitude.numeric'       => 'خط الطول يجب أن يكون رقماً.',
            'longitude.between'       => 'خط الطول يجب أن يكون بين -180 و 180 درجة.',
            'radius.numeric'          => 'نصف القطر يجب أن يكون رقماً.',
            'radius.min'              => 'نصف القطر لا يمكن أن يكون أقل من 0.',
        ];
    }
}
