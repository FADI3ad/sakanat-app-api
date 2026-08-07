<?php

namespace App\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $areaId = $this->route('area')?->id ?? $this->route('area');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('areas', 'name')->ignore($areaId)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المنطقة مطلوب.',
            'name.string'   => 'اسم المنطقة يجب أن يكون نصاً.',
            'name.max'      => 'اسم المنطقة لا يجب أن يتجاوز 255 حرفاً.',
            'name.unique'   => 'اسم المنطقة مستخدم بالفعل.',
        ];
    }
}
