<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrderAccessService::class);
    }

    /** @test */
    public function allows_access_when_user_id_matches(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'telegram_id' => 12345]);

        $this->assertTrue($this->service->canAccessOrder($order, $user, null));
    }

    /** @test */
    public function allows_access_when_telegram_id_matches(): void
    {
        $order = Order::factory()->create(['user_id' => null, 'telegram_id' => 99999]);

        $this->assertTrue($this->service->canAccessOrder($order, null, 99999));
    }

    /** @test */
    public function denies_access_when_user_id_does_not_match(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id, 'telegram_id' => 12345]);

        $this->assertFalse($this->service->canAccessOrder($order, $user, null));
    }

    /** @test */
    public function denies_access_when_telegram_id_does_not_match(): void
    {
        $order = Order::factory()->create(['user_id' => null, 'telegram_id' => 11111]);

        $this->assertFalse($this->service->canAccessOrder($order, null, 22222));
    }

    /** @test */
    public function denies_access_when_no_user_and_no_telegram(): void
    {
        $order = Order::factory()->create(['user_id' => 1, 'telegram_id' => 12345]);

        $this->assertFalse($this->service->canAccessOrder($order, null, null));
    }

    /** @test */
    public function allows_access_when_user_matches_even_with_different_telegram(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'telegram_id' => 55555]);

        $this->assertTrue($this->service->canAccessOrder($order, $user, 99999));
    }

    /** @test */
    public function deny_if_cannot_access_returns_403_response(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->service->denyIfCannotAccess($order, $user, null, 'test');

        $this->assertNotNull($response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function deny_if_cannot_access_returns_null_when_allowed(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->service->denyIfCannotAccess($order, $user, null, 'test');

        $this->assertNull($response);
    }
}
