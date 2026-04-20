<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('event')->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(EventStatus::class)],
            'comment' => ['nullable', 'string', 'max:1000'],
            'postponed_until' => ['nullable', 'required_if:status,postponed', 'date', 'after:now'],
            'completed_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }
}
