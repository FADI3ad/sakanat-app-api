<?php

namespace App\Http\Requests\Message;

use App\Enums\UserTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return in_array($user->type, [
            UserTypeEnum::ADMIN,
            UserTypeEnum::PROVIDER,
            UserTypeEnum::RESIDENT,
        ]);
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'غير مصرح لك بإرسال رسائل.',
        ], 403));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'receiver_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('type', [
                        UserTypeEnum::ADMIN->value,
                        UserTypeEnum::PROVIDER->value,
                        UserTypeEnum::RESIDENT->value,
                    ]);
                }),
                function ($attribute, $value, $fail) {
                    if ($value == $this->user()->id) {
                        $fail('لا يمكنك إرسال رسالة لنفسك.');
                    }
                }
            ],
            'message' => ['required_without:file', 'nullable', 'string', 'min:1'],
            'file'    => ['required_without:message', 'nullable', 'file', 'max:10240'], // 10MB max
        ];
    }

    /**
     * Get custom error messages for validator failures.
     */
    public function messages(): array
    {
        return [
            'receiver_id.required'     => 'معرّف المستلم مطلوب.',
            'receiver_id.integer'      => 'معرّف المستلم يجب أن يكون رقماً صحيحاً.',
            'receiver_id.exists'       => 'المستلم غير موجود أو لا ينتمي للمستخدمين المتاح مراسلتهم.',
            'message.required_without' => 'يجب إدخال نص الرسالة أو إرفاق ملف.',
            'message.string'           => 'محتوى الرسالة يجب أن يكون نصاً.',
            'message.min'              => 'الرسالة لا يمكن أن تكون فارغة.',
            'file.required_without'    => 'يجب إدخال نص الرسالة أو إرفاق ملف.',
            'file.file'                => 'الملف المرفق غير صالح.',
            'file.max'                 => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
        ];
    }
}
