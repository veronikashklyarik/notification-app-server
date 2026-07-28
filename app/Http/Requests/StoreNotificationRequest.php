<?php

namespace App\Http\Requests;

use App\Enums\ScheduleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:2000'],
            'schedule_type' => ['required', Rule::enum(ScheduleType::class)],
            'week_days' => ['nullable', 'required_if:schedule_type,week_days', 'array', 'min:1'],
            'week_days.*.day' => ['required', 'integer', 'between:1,7'],
            'week_days.*.times' => ['required', 'array', 'min:1'],
            'week_days.*.times.*' => ['date_format:H:i'],
            'every_n_days' => ['nullable', 'required_if:schedule_type,every_n_days', 'integer', 'min:1', 'max:365'],
            'cyclical_value' => ['nullable', 'required_if:schedule_type,cyclical', 'integer', 'min:1'],
            'cyclical_unit' => ['nullable', 'required_if:schedule_type,cyclical', Rule::in(['days', 'weeks', 'months', 'years'])],
            'times' => ['nullable', 'array'],
            'times.*' => ['date_format:H:i'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ];
    }
}
