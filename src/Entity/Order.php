<?php

namespace App\Entity;

use App\Enum\PaymentStatus;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;
use \App\Enum\OrderStatus;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[Broadcast(topics: [
    '@="pizza-order-" ~ entity.getId()',
    'pizza-orders',
])]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id ;

    #[ORM\Column(length: 255)]
    private string $customer;

    #[ORM\Column(nullable: false)]
    private int $totalCoins;

    #[ORM\Column(enumType: OrderStatus::class)]
    private OrderStatus $status ;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $taskId = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $items;

    /**
     * @var Collection<int, OrderLog>
     */
    #[ORM\OneToMany(targetEntity: OrderLog::class, mappedBy: 'owner', cascade: ["PERSIST"], orphanRemoval: true)]
    private Collection $logs;

    #[ORM\Column(enumType: PaymentStatus::class)]
    private PaymentStatus $paymentStatus;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transactionNumber = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->logs = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->totalCoins = 1;
        $this->status = OrderStatus::Pending;
        $this->paymentStatus = PaymentStatus::Pending;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?string
    {
        return $this->customer;
    }

    public function setCustomer(string $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getTotalCoins(): int
    {
        return $this->totalCoins;
    }

    public function setTotalCoins(int $totalCoins): static
    {
        $this->totalCoins = $totalCoins;

        return $this;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTaskId(): ?string
    {
        return $this->taskId;
    }

    public function setTaskId(?string $taskId): static
    {
        $this->taskId = $taskId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOwner($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOwner() === $this) {
                $item->setOwner(null);
            }
        }

        return $this;
    }

    public function addToLog(string $message): static
    {
        $log = new OrderLog();
        $log->setMessage($message);
        $this->addLog($log);
        return $this;
    }

    /**
     * @return Collection<int, OrderLog>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(OrderLog $log): static
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setOwner($this);
        }

        return $this;
    }

    public function removeLog(OrderLog $log): static
    {
        if ($this->logs->removeElement($log)) {
            if ($log->getOwner() === $this) {
                $log->setOwner(null);
            }
        }

        return $this;
    }

    public function getPaymentStatus(): PaymentStatus
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(PaymentStatus $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getTransactionNumber(): ?string
    {
        return $this->transactionNumber;
    }

    public function setTransactionNumber(?string $transactionNumber): static
    {
        $this->transactionNumber = $transactionNumber;

        return $this;
    }
}
