<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Models\Bot;
use App\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем тестового пользователя для авторизации
        $this->user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function can_get_order_status_history()
    {
        // Создаем бота
        $bot = Bot::factory()->create();

        // Создаем заказ
        $order = Order::factory()->create([
            'bot_id' => $bot->id,
            'status' => Order::STATUS_NEW,
        ]);

        // Создаем историю статусов
        OrderStatusHistory::factory()->count(3)->create([
            'order_id' => $order->id,
            'status' => Order::STATUS_ACCEPTED,
            'role' => 'admin',
        ]);

        // Авторизуемся
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}/status-history");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'order_id',
                        'status',
                        'previous_status',
                        'role',
                        'comment',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function can_filter_status_history_by_role()
    {
        $bot = Bot::factory()->create();
        $order = Order::factory()->create(['bot_id' => $bot->id]);

        OrderStatusHistory::factory()->create([
            'order_id' => $order->id,
            'role' => 'admin',
            'status' => Order::STATUS_ACCEPTED,
        ]);

        OrderStatusHistory::factory()->create([
            'order_id' => $order->id,
            'role' => 'kitchen',
            'status' => Order::STATUS_SENT_TO_KITCHEN,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}/status-history?role=admin");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('admin', $data[0]['role']);
    }

    /** @test */
    public function can_filter_status_history_by_status()
    {
        $bot = Bot::factory()->create();
        $order = Order::factory()->create(['bot_id' => $bot->id]);

        OrderStatusHistory::factory()->create([
            'order_id' => $order->id,
            'status' => Order::STATUS_ACCEPTED,
        ]);

        OrderStatusHistory::factory()->create([
            'order_id' => $order->id,
            'status' => Order::STATUS_SENT_TO_KITCHEN,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}/status-history?status=" . Order::STATUS_ACCEPTED);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals(Order::STATUS_ACCEPTED, $data[0]['status']);
    }

    /** @test */
    public function status_history_returns_empty_array_for_non_existent_order()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/orders/99999/status-history');

        $response->assertStatus(404);
    }

    /** @test */
    public function status_history_requires_authentication()
    {
        $bot = Bot::factory()->create();
        $order = Order::factory()->create(['bot_id' => $bot->id]);

        $response = $this->getJson("/api/v1/orders/{$order->id}/status-history");

        $response->assertStatus(401);
    }
}




