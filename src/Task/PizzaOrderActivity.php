<?php

declare(strict_types=1);

namespace App\Task;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface]
readonly class PizzaOrderActivity
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[ActivityMethod]
    public function markPaymentSuccess(int $orderId): void
    {
        $this->em->wrapInTransaction(function () use ($orderId) {
            $this->order($orderId)
                ->setPaymentStatus(PaymentStatus::Paid)
                ->setStatus(OrderStatus::WaitingForRestaurant)
                ->addToLog('Payment successful. Waiting for restaurant to confirm.');
        });
    }

    #[ActivityMethod]
    public function markPaymentDeclined(int $orderId): void
    {
        $this->em->wrapInTransaction(function () use ($orderId) {
            $this->order($orderId)
                ->setPaymentStatus(PaymentStatus::Failed)
                ->setStatus(OrderStatus::Failed)
                ->addToLog('Payment was declined.');
        });
    }

    #[ActivityMethod]
    public function markPaymentTimeout(int $orderId): void
    {
        $this->em->wrapInTransaction(function () use ($orderId) {
            $this->order($orderId)
                ->setPaymentStatus(PaymentStatus::TimeOut)
                ->setStatus(OrderStatus::Failed)
                ->addToLog('Payment not received within time.');
        });
    }

    #[ActivityMethod]
    public function markRestaurantAccepted(int $orderId): void
    {
        $this->em->wrapInTransaction(function () use ($orderId) {
            $this->order($orderId)
                ->setStatus(OrderStatus::Baking)
                ->addToLog('Restaurant accepted the order! Baking started.');
        });
    }

    #[ActivityMethod]
    public function markRestaurantDenied(int $orderId): void
    {
        $this->em->wrapInTransaction(function () use ($orderId) {
            $this->order($orderId)
                ->setStatus(OrderStatus::Failed)
                ->setPaymentStatus(PaymentStatus::Refunded)
                ->addToLog('Restaurant denied the order. Refunding payment...');
        });
    }

    #[ActivityMethod]
    public function markRestaurantTimeout(int $orderId): void
    {
        $this->em->wrapInTransaction(function () use ($orderId) {
            $this->order($orderId)
                ->setStatus(OrderStatus::Failed)
                ->addToLog('Restaurant did not respond in time. Refunding payment...');
        });
    }

    #[ActivityMethod]
    public function markBaked(int $orderId): void
    {
        $this->em->wrapInTransaction(function () use ($orderId) {
            $this->order($orderId)
                ->setStatus(OrderStatus::WaitingForDriver)
                ->addToLog('Pizza is baked! Waiting for a driver to pick it up.');
        });
    }

    #[ActivityMethod]
    public function markDriverAssigned(int $orderId, string $driverName): void
    {
        $this->em->wrapInTransaction(function () use ($orderId, $driverName) {
            $this->order($orderId)
                ->setStatus(OrderStatus::Delivering)
                ->addToLog("$driverName picked up your pizza and is on the way!");
        });
    }

    #[ActivityMethod]
    public function markDelivered(int $orderId): void
    {
        $this->em->wrapInTransaction(function () use ($orderId) {
            $this->order($orderId)
                ->setStatus(OrderStatus::Delivered)
                ->addToLog('Pizza delivered successfully! Enjoy your meal.');
        });
    }

    #[ActivityMethod]
    public function markRefunded(int $orderId): void
    {
        $this->em->wrapInTransaction(function () use ($orderId) {
            $this->order($orderId)
                ->setPaymentStatus(PaymentStatus::Refunded)
                ->addToLog('Payment refunded.');
        });
    }

    private function order(int $orderId): Order
    {
        return $this->em->find(Order::class, $orderId);
    }
}
