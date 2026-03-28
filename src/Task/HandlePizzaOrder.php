<?php

declare(strict_types=1);

namespace App\Task;

use Temporal\Activity\ActivityOptions;
use Temporal\Workflow;
use Temporal\Workflow\SignalMethod;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
class HandlePizzaOrder
{
    /**
     * @var PizzaOrderActivity
     */
    private $activity;

    private ?array $paymentSignal = null;
    private ?array $restaurantSignal = null;
    private ?array $driverSignal = null;
    private bool $deliveredSignal = false;

    public function __construct()
    {
        $this->activity = Workflow::newActivityStub(
            PizzaOrderActivity::class,
            ActivityOptions::new()->withStartToCloseTimeout(30),
        );
    }

    #[WorkflowMethod]
    public function handle(OrderPizzaPayload $payload): \Generator
    {
        $orderId = $payload->orderId;

        $timedOut = !yield Workflow::awaitWithTimeout(300, fn() => $this->paymentSignal !== null);

        if ($timedOut) {
            yield $this->activity->markPaymentTimeout($orderId);
            return ['status' => 'failed'];
        }

        if ($this->paymentSignal['status'] === 'SUCCESS') {
            yield $this->activity->markPaymentSuccess($orderId);
            return yield from $this->handleRestaurantConfirmation($orderId);
        }

        yield $this->activity->markPaymentDeclined($orderId);
        return ['status' => 'failed'];
    }

    private function handleRestaurantConfirmation(int $orderId): \Generator
    {
        $timedOut = !yield Workflow::awaitWithTimeout(300, fn() => $this->restaurantSignal !== null);

        if ($timedOut) {
            yield $this->activity->markRestaurantTimeout($orderId);
            return yield from $this->refund($orderId);
        }

        if ($this->restaurantSignal['status'] === 'ACCEPTED') {
            yield $this->activity->markRestaurantAccepted($orderId);
            yield Workflow::timer(30);
            yield $this->activity->markBaked($orderId);

            yield Workflow::await(fn() => $this->driverSignal !== null);
            $driverName = $this->driverSignal['driverName'] ?? 'A driver';
            yield $this->activity->markDriverAssigned($orderId, $driverName);

            yield Workflow::await(fn() => $this->deliveredSignal);
            yield $this->activity->markDelivered($orderId);

            return ['status' => 'done', 'driver' => $driverName];
        }

        yield $this->activity->markRestaurantDenied($orderId);
        return yield from $this->refund($orderId);
    }

    private function refund(int $orderId): \Generator
    {
        yield Workflow::timer(30);
        yield $this->activity->markRefunded($orderId);
        return ['status' => 'failed'];
    }

    #[SignalMethod]
    public function payment(array $data): void
    {
        $this->paymentSignal = $data;
    }

    #[SignalMethod]
    public function restaurantConfirmation(array $data): void
    {
        $this->restaurantSignal = $data;
    }

    #[SignalMethod]
    public function driverAssigned(array $data): void
    {
        $this->driverSignal = $data;
    }

    #[SignalMethod]
    public function delivered(): void
    {
        $this->deliveredSignal = true;
    }
}
