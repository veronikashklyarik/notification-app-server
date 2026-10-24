<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/**
 * A push opens the page with ?event=<id>, and that event is highlighted so it's clear which
 * one the push was about. Once the event is marked the highlight has done its job.
 */
trait HighlightsPushedEvent
{
    #[Url(as: 'event')]
    public ?string $highlightEventId = null;

    /**
     * @param  array<int, string>  $markedEventIds
     */
    protected function forgetHighlight(array $markedEventIds): void
    {
        if (in_array($this->highlightEventId, $markedEventIds, true)) {
            $this->highlightEventId = null;
        }
    }
}
