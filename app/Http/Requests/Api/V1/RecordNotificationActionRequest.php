<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\HistoryAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordNotificationActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('notification')->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(HistoryAction::class)],
            'comment' => ['nullable', 'string', 'max:1000'],
            'postponed_until' => ['nullable', 'required_if:action,Postponed', 'date', 'after:now'],
        ];
    }
}
