<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\ValidPushEndpoint;
use Illuminate\Foundation\Http\FormRequest;

class StoreWebPushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'string', 'url', 'max:500', new ValidPushEndpoint],
            'keys.p256dh' => ['required', 'string', 'max:512'],
            'keys.auth' => ['required', 'string', 'max:128'],
        ];
    }
}
