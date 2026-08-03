<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'bed_id'      => ['required', 'integer', 'exists:beds,id'],
            'latitude'    => ['required', 'numeric', 'between:-90,90'],
            'longitude'   => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.required' => 'معرّف السكن مطلوب.',
            'property_id.exists'   => 'السكن المحدد غير موجود.',
            'bed_id.required'      => 'معرّف السرير مطلوب.',
            'bed_id.exists'        => 'السرير المحدد غير موجود.',
            'latitude.required'    => 'خط العرض مطلوب.',
            'latitude.between'     => 'خط العرض غير صالح.',
            'longitude.required'   => 'خط الطول مطلوب.',
            'longitude.between'    => 'خط الطول غير صالح.',
        ];
    }
}
