<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users')->withoutTrashed()],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'device_name' => ['required', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'timezone:all'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $isRestorable = User::onlyTrashed()
                    ->where('email', $this->input('email'))
                    ->where('deleted_at', '>', now()->subDays(User::RESTORE_DAYS))
                    ->exists();

                if ($isRestorable) {
                    $validator->errors()->add('email', 'This account was deleted recently. Sign in to restore it.');
                }
            },
        ];
    }
}
