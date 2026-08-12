<?php

namespace App\Http\Requests\Absence;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbsenceRequest extends FormRequest
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
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'reason'     => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages in Arabic.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required'       => 'تاريخ بداية الغياب مطلوب.',
            'start_date.date'           => 'صيغة تاريخ البداية غير صحيحة.',
            'start_date.after_or_equal' => 'تاريخ البداية يجب أن يكون اليوم أو في المستقبل.',
            'end_date.required'         => 'تاريخ نهاية الغياب مطلوب.',
            'end_date.date'             => 'صيغة تاريخ النهاية غير صحيحة.',
            'end_date.after_or_equal'   => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية.',
            'reason.max'                => 'سبب الغياب لا يجب أن يتجاوز 1000 حرف.',
        ];
    }
}
