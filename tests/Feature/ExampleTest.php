<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Guests get the public landing page so Google can verify the OAuth app.
     */
    public function test_the_application_shows_the_landing_page_to_guests(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
