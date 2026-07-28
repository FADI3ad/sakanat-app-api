<?php

namespace App\Http\Requests\UtilityBill;

use App\Enums\UtilityTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreUtilityBillRequest extends FormRequest
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
            'type'   => ['required', new Enum(UtilityTypeEnum::class)],
            'month'  => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes'  => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required'   => 'نوع الفاتورة مطلوب.',
            'type.enum'       => 'نوع الفاتورة غير صحيح. القيم المتاحة: electricity, water, gas, other.',
            'month.required'  => 'الشهر مطلوب.',
            'month.regex'     => 'صيغة الشهر غير صحيحة. استخدم YYYY-MM (مثال: 2026-07).',
            'amount.numeric'  => 'المبلغ يجب أن يكون رقماً.',
            'amount.min'      => 'المبلغ لا يمكن أن يكون سالباً.',
        ];
    }
}
