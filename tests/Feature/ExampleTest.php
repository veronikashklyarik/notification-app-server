<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('home'));
    }

    public function test_intentional_failure(): void
    {
        $this->assertTrue(false, 'This test intentionally fails to verify CI blocks the merge.');
    }
}
