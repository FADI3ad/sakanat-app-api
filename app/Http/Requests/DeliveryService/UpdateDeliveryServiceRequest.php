<?php

namespace App\Http\Requests\DeliveryService;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'required', 'string', 'max:255'],
            'phone'        => ['sometimes', 'required', 'string', 'max:255'],
            'vehicle_type' => ['sometimes', 'required', 'string', 'max:255'],
            'is_available' => ['nullable', 'boolean'],
            'type_id'      => ['sometimes', 'required', 'integer', 'exists:types,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'اسم مقدم خدمة التوصيل مطلوب.',
            'name.string'           => 'الاسم يجب أن يكون نصاً.',
            'name.max'              => 'الاسم يجب ألا يتجاوز 255 حرفاً.',
            'phone.required'        => 'رقم الهاتف مطلوب.',
            'phone.string'          => 'رقم الهاتف يجب أن يكون نصاً.',
            'phone.max'             => 'رقم الهاتف يجب ألا يتجاوز 255 حرفاً.',
            'vehicle_type.required' => 'نوع المركبة مطلوب.',
            'vehicle_type.string'   => 'نوع المركبة يجب أن يكون نصاً.',
            'vehicle_type.max'      => 'نوع المركبة يجب ألا يتجاوز 255 حرفاً.',
            'is_available.boolean'  => 'حالة الإتاحة يجب أن تكون قيمة منطقية (true/false).',
            'type_id.required'      => 'نوع الخدمة مطلوب.',
            'type_id.integer'       => 'معرف نوع الخدمة يجب أن يكون رقماً صحيحاً.',
            'type_id.exists'        => 'نوع الخدمة المختار غير موجود.',
        ];
    }
}
