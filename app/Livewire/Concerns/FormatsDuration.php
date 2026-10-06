<?php

namespace App\Livewire\Concerns;

trait FormatsDuration
{
    public function formatMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return __(':count min', ['count' => $minutes]);
        }

        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        return $remainder > 0
            ? __(':hours h :minutes min', ['hours' => $hours, 'minutes' => $remainder])
            : __(':count h', ['count' => $hours]);
    }
}
