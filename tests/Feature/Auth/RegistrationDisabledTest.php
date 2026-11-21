<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class RegistrationDisabledTest extends TestCase
{
    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'x', 'email' => 'x@example.com', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertNotFound();
    }
}
