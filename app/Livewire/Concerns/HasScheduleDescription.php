<?php

namespace App\Livewire\Concerns;

use App\Models\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Pure sentence-formatting for the schedule summary shown in the sheet.
 * No Livewire state mutation — every method here only reads schedule
 * properties and returns a string. Split out of HasScheduleFields, which
 * owns the form-mutation side (addTime, toggleWeekDay, validation, etc.).
 */
trait HasScheduleDescription
{
    private function everyDayDescription(): string
    {
        $times = $this->andJoin($this->times);
        $from = $this->startsTodayOrOn();
        $sentence = __('Every day at :times, from :from', ['times' => $times, 'from' => $from]);

        return $sentence.'.'.$this->trailingClause();
    }

    private function weekDaysDescription(): string
    {
        if (empty($this->week_days)) {
            return __('Choose days of the week below');
        }

        $dayNames = $this->pluralDayNameMap();
        $sorted = $this->week_days;
        usort($sorted, fn ($a, $b) => (int) $a['day'] <=> (int) $b['day']);

        /** @var array<string, array{days: array<int, int>, times: array<int, string>}> $groups */
        $groups = [];
        foreach ($sorted as $entry) {
            $times = $entry['times'] ?? [];
            sort($times);
            $key = implode(',', $times);
            $groups[$key]['times'] = $times;
            $groups[$key]['days'][] = (int) $entry['day'];
        }

        $parts = array_map(function ($group) use ($dayNames) {
            $dayLabel = $this->andJoin(array_map(fn ($d) => $dayNames[$d] ?? '', $group['days']));
            $timesLabel = ! empty($group['times']) ? ' '.__('at :times', ['times' => $this->andJoin($group['times'])]) : '';

            return $dayLabel.$timesLabel;
        }, $groups);

        $sentence = implode(', ', $parts);

        return $sentence.'.'.$this->trailingClause();
    }

    private function specificDatesDescription(): string
    {
        if (empty($this->specific_dates)) {
            return __('No dates added yet');
        }

        $sorted = $this->specific_dates;
        usort($sorted, fn ($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));

        $parts = array_filter(array_map(function ($entry) {
            $date = $entry['date'] ?? null;
            if (! $date) {
                return null;
            }

            $label = Carbon::createFromFormat('Y-m-d', $date)->translatedFormat('D j M');
            $times = $entry['times'] ?? [];
            if (! empty($times)) {
                $label .= ' '.__('at :times', ['times' => $this->andJoin($times)]);
            }

            return $label;
        }, $sorted));

        if (empty($parts)) {
            return __('No dates added yet');
        }

        $list = __('On :list', ['list' => $this->andJoin($parts)]);

        if (! $this->is_active) {
            return $list.'.'.$this->trailingClause();
        }

        return $list.'. '.__('After that it ends.');
    }

    private function cyclicalDescription(): string
    {
        $n = $this->cyclical_value ?? 1;
        $unit = $this->cyclical_unit;
        $dayNames = $this->dayNameMap();
        $monthAbbr = $this->monthAbbrMap();
        $posLabels = $this->positionLabelMap();
        $allTimes = ! empty($this->times) ? (' '.__('at :times', ['times' => $this->andJoin($this->times)])) : '';

        if ($unit === 'days' && $this->cyclical_use_for) {
            $times = $this->andJoin($this->times);
            $useFor = trans_choice(':count day|:count days', (int) $this->cyclical_use_for, ['count' => $this->cyclical_use_for]);
            $pauseFor = trans_choice(':count day|:count days', (int) ($this->cyclical_pause_for ?? 0), ['count' => $this->cyclical_pause_for ?? 0]);
            $start = $this->startsTodayOrOn();
            $sentence = __('Every day at :times for :use, then :pause off, and again — starting :start', [
                'times' => $times, 'use' => $useFor, 'pause' => $pauseFor, 'start' => $start,
            ]);

            return $sentence.'.'.$this->trailingClause();
        }

        if ($unit === 'weeks') {
            $base = trans_choice('Every :count week|Every :count weeks', $n, ['count' => $n]);
            if (! empty($this->cyclical_week_days)) {
                $sorted = $this->cyclical_week_days;
                sort($sorted);
                $base .= ' '.__('on').' '.implode(', ', array_map(fn ($d) => $dayNames[(int) $d] ?? $d, $sorted));
            }

            return $base.$allTimes.'.'.$this->trailingClause();
        }

        if ($unit === 'months') {
            if ($this->cyclical_month_type === 'each' && ! empty($this->cyclical_month_days)) {
                $sorted = $this->cyclical_month_days;
                sort($sorted);
                $dayStr = $this->andJoin(array_map(fn ($d) => $this->ordinal((int) $d), $sorted));
                $period = $n > 1
                    ? trans_choice('of every :count month|of every :count months', $n, ['count' => $n])
                    : __('of every month');
                $times = $this->andJoin($this->times);
                $sentence = __('On the :days :period at :times', ['days' => $dayStr, 'period' => $period, 'times' => $times]);

                return $sentence.'.'.$this->trailingClause();
            }

            $base = trans_choice('Every :count month|Every :count months', $n, ['count' => $n]);
            if ($this->cyclical_month_type === 'on_the' && $this->cyclical_month_position && $this->cyclical_month_weekday) {
                $pos = $posLabels[$this->cyclical_month_position] ?? $this->cyclical_month_position;
                $day = $dayNames[(int) $this->cyclical_month_weekday] ?? $this->cyclical_month_weekday;

                return $base.' '.__('on the :pos :day', ['pos' => $pos, 'day' => $day]).$allTimes.'.'.$this->trailingClause();
            }

            return $base.$allTimes.'.'.$this->trailingClause();
        }

        if ($unit === 'years') {
            $base = trans_choice('Every :count year|Every :count years', $n, ['count' => $n]);
            if (! empty($this->cyclical_year_months)) {
                $sorted = $this->cyclical_year_months;
                sort($sorted);
                $base .= ' '.__('in').' '.implode(', ', array_map(fn ($m) => $monthAbbr[(int) $m] ?? $m, $sorted));
            }
            if ($this->cyclical_year_use_weekday && $this->cyclical_month_position && $this->cyclical_month_weekday) {
                $pos = $posLabels[$this->cyclical_month_position] ?? $this->cyclical_month_position;
                $day = $dayNames[(int) $this->cyclical_month_weekday] ?? $this->cyclical_month_weekday;
                $base .= ' '.__('on the :pos :day', ['pos' => $pos, 'day' => $day]);
            } elseif (! $this->cyclical_year_use_weekday && $this->cyclical_year_day) {
                $base .= ' '.__('on the :day', ['day' => $this->ordinal((int) $this->cyclical_year_day)]);
            }

            return $base.$allTimes.'.'.$this->trailingClause();
        }

        return trans_choice('Every :count day|Every :count days', $n, ['count' => $n]).$allTimes.'.'.$this->trailingClause();
    }

    /**
     * The shared ending for every schedule sentence except Specific dates
     * (which ends with "After that it ends." instead when active) and As
     * needed (which has its own fixed sentence). Inactive always wins over
     * the repeat-interval clause, matching the mockup.
     */
    private function trailingClause(): string
    {
        if (! $this->is_active) {
            return ' '.__('Notifications are off — it stays in the list, silent.');
        }

        if ($this->reminderInterval ?? null) {
            $interval = Str::lower(__(Notification::REMINDER_INTERVALS[$this->reminderInterval] ?? ''));

            return ' '.__('If you don’t mark it, it repeats :interval.', ['interval' => $interval]);
        }

        return '';
    }

    /**
     * "today" when starts_at is today (or unset), otherwise the formatted date.
     */
    private function startsTodayOrOn(): string
    {
        $start = $this->starts_at ? Carbon::parse($this->starts_at) : now();

        return $start->isToday() ? __('today') : $start->translatedFormat('j M');
    }

    private function ordinal(int $n): string
    {
        $locale = app()->getLocale();
        if ($locale !== 'en') {
            // Laravel's __() falls back to the raw key when a translation resolves to
            // an empty string, so locales with no suffix (Polish) store a placeholder
            // space instead of "" — trim it back out here rather than leaking it (or a
            // double space from the surrounding sentence template) into the UI.
            $suffix = trim(__('ordinal_suffix'));

            return $suffix === '' ? (string) $n : $n.$suffix;
        }
        $mod100 = $n % 100;
        $mod10 = $n % 10;
        if ($mod100 >= 11 && $mod100 <= 13) {
            return $n.'th';
        }

        return $n.match ($mod10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }

    private function andJoin(array $items): string
    {
        if (count($items) <= 1) {
            return implode('', $items);
        }
        $last = array_pop($items);

        return implode(', ', $items).' '.__('and').' '.$last;
    }

    /** @return array<int, string> */
    private function dayNameMap(): array
    {
        return [1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'), 5 => __('Fri'), 6 => __('Sat'), 7 => __('Sun')];
    }

    /** @return array<int, string> */
    private function pluralDayNameMap(): array
    {
        return [1 => __('Mondays'), 2 => __('Tuesdays'), 3 => __('Wednesdays'), 4 => __('Thursdays'), 5 => __('Fridays'), 6 => __('Saturdays'), 7 => __('Sundays')];
    }

    /** @return array<int, string> */
    private function monthAbbrMap(): array
    {
        return [1 => __('Jan'), 2 => __('Feb'), 3 => __('Mar'), 4 => __('Apr'), 5 => __('May'), 6 => __('Jun'), 7 => __('Jul'), 8 => __('Aug'), 9 => __('Sep'), 10 => __('Oct'), 11 => __('Nov'), 12 => __('Dec')];
    }

    /** @return array<string, string> */
    private function positionLabelMap(): array
    {
        return ['first' => __('1st'), 'second' => __('2nd'), 'third' => __('3rd'), 'fourth' => __('4th'), 'fifth' => __('5th'), 'last' => __('last')];
    }
}
