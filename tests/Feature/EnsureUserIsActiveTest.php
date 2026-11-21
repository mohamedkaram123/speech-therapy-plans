<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureUserIsActiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware(['web', 'auth', 'active'])->get('/_active-probe', fn () => 'ok');
    }

    public function test_active_user_passes(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get('/_active-probe')->assertOk();
    }

    public function test_inactive_user_is_logged_out_with_arabic_message(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)->get('/_active-probe')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'تم تعطيل حسابك. يرجى التواصل مع المشرف.']);
        $this->assertGuest();
    }
}
