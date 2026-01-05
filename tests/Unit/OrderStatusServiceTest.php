<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\TelegramUser;
use App\Models\Bot;
use App\Services\Order\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderStatusService();
    }

    /** @test */
    public function can_change_order_status()
    {
        $bot = Bot::factory()->create();
        $order = Order::factory()->create([
            'bot_id' => $bot->id,
            'status' => Order::STATUS_NEW,
        ]);

        $result = $this->service->changeStatus(
            $order,
            Order::STATUS_ACCEPTED,
            ['role' => 'admin', 'comment' => 'Тестовый комментарий']
        );

        $this->assertTrue($result);
        $order->refresh();
        $this->assertEquals(Order::STATUS_ACCEPTED, $order->status);
    }

    /** @test */
    public function logs_status_change_in_history()
    {
        $bot = Bot::factory()->create();
        $order = Order::factory()->create([
            'bot_id' => $bot->id,
            'status' => Order::STATUS_NEW,
        ]);

        $this->service->changeStatus(
            $order,
            Order::STATUS_ACCEPTED,
            ['role' => 'admin', 'comment' => 'Тестовый комментарий']
        );

        $history = OrderStatusHistory::where('order_id', $order->id)->first();
        
        $this->assertNotNull($history);
        $this->assertEquals(Order::STATUS_ACCEPTED, $history->status);
        $this->assertEquals(Order::STATUS_NEW, $history->previous_status);
        $this->assertEquals('admin', $history->role);
        $this->assertEquals('Тестовый комментарий', $history->comment);
    }

    /** @test */
    public function cannot_change_status_from_delivered()
    {
        $bot = Bot::factory()->create();
        $order = Order::factory()->create([
            'bot_id' => $bot->id,
            'status' => Order::STATUS_DELIVERED,
        ]);

        $result = $this->service->changeStatus(
            $order,
            Order::STATUS_ACCEPTED,
            'admin'
        );

        $this->assertFalse($result);
        $order->refresh();
        $this->assertEquals(Order::STATUS_DELIVERED, $order->status);
    }

    /** @test */
    public function cannot_change_status_from_cancelled()
    {
        $bot = Bot::factory()->create();
        $order = Order::factory()->create([
            'bot_id' => $bot->id,
            'status' => Order::STATUS_CANCELLED,
        ]);

        $result = $this->service->changeStatus(
            $order,
            Order::STATUS_ACCEPTED,
            ['role' => 'admin']
        );

        $this->assertFalse($result);
        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->status);
    }

    /** @test */
    public function can_get_status_history()
    {
        $bot = Bot::factory()->create();
        $order = Order::factory()->create(['bot_id' => $bot->id]);

        OrderStatusHistory::factory()->count(3)->create([
            'order_id' => $order->id,
        ]);

        $history = $this->service->getStatusHistory($order);

        $this->assertCount(3, $history);
    }

    /** @test */
    public function validates_status_transitions_for_admin()
    {
        $bot = Bot::factory()->create();
        $order = Order::factory()->create([
            'bot_id' => $bot->id,
            'status' => Order::STATUS_NEW,
        ]);

        // Админ может изменить новый заказ на принятый
        $this->assertTrue(
            $this->service->canChangeStatus($order, Order::STATUS_ACCEPTED, TelegramUser::ROLE_ADMIN)
        );

        // Админ не может изменить новый заказ сразу на доставленный
        $this->assertFalse(
            $this->service->canChangeStatus($order, Order::STATUS_DELIVERED, TelegramUser::ROLE_ADMIN)
        );
    }

    /** @test */
    public function validates_status_transitions_for_kitchen()
    {
        $bot = Bot::factory()->create();
        $order = Order::factory()->create([
            'bot_id' => $bot->id,
            'status' => Order::STATUS_SENT_TO_KITCHEN,
        ]);

        // Кухня может принять заказ
        $this->assertTrue(
            $this->service->canChangeStatus($order, Order::STATUS_KITCHEN_ACCEPTED, TelegramUser::ROLE_KITCHEN)
        );

        // Кухня не может изменить статус нового заказа
        $order->status = Order::STATUS_NEW;
        $order->save();
        $this->assertFalse(
            $this->service->canChangeStatus($order, Order::STATUS_ACCEPTED, TelegramUser::ROLE_KITCHEN)
        );
    }

    /** @test */
    public function validates_status_transitions_for_courier()
    {
        $bot = Bot::factory()->create();
        $order = Order::factory()->create([
            'bot_id' => $bot->id,
            'status' => Order::STATUS_COURIER_ASSIGNED,
        ]);

        // Курьер может забрать заказ
        $this->assertTrue(
            $this->service->canChangeStatus($order, Order::STATUS_IN_TRANSIT, TelegramUser::ROLE_COURIER)
        );

        // Курьер не может изменить новый заказ
        $order->status = Order::STATUS_NEW;
        $order->save();
        $this->assertFalse(
            $this->service->canChangeStatus($order, Order::STATUS_ACCEPTED, TelegramUser::ROLE_COURIER)
        );
    }
}

