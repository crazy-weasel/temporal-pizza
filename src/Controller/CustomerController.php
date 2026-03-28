<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\PizzaOrder;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;
use App\Repository\PizzaRepository;
use App\Task\HandlePizzaOrder;
use App\Task\OrderPizzaPayload;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowOptions;

#[Route('/customer')]
class CustomerController extends AbstractController
{
    public function __construct(private readonly WorkflowClientInterface $workflowClient)
    {
    }

    #[Route('/menu', name: 'customer_menu')]
    public function menu(PizzaRepository $pizzas, OrderRepository $orders): Response
    {
        return $this->render('customer/menu.html.twig', [
            'pizzas' => $pizzas->findAll(),
            'recentOrders' => $orders->findRecent(),
        ]);
    }

    #[Route('/order', name: 'customer_order', methods: ['POST'])]
    public function placeOrder(
        PizzaRepository $pizzaRepository,
        EntityManagerInterface $em,
        #[MapRequestPayload] PizzaOrder $pizzaOrder,
    ): Response {
        $order = $em->wrapInTransaction(function () use ($em, $pizzaRepository, $pizzaOrder) {
            $order = new Order();
            $order->setCustomer($pizzaOrder->email);
            $order->setTotalCoins($pizzaOrder->coins);

            foreach ($pizzaOrder->items as $item) {
                $pizza = $pizzaRepository->find($item->pizzaId);
                if ($pizza === null) {
                    throw new BadRequestHttpException('Invalid Pizza with id ' . $item->pizzaId);
                }
                $orderItem = new OrderItem();
                $orderItem->setPizza($pizza);
                $orderItem->setQuantity($item->quantity);
                $order->addItem($orderItem);
                $em->persist($orderItem);
            }

            $order->addToLog('Order created. Waiting for payment.');

            $em->persist($order);
            $em->flush();

            $this->workflowClient->start(
                $this->workflowClient->newWorkflowStub(
                    HandlePizzaOrder::class,
                    WorkflowOptions::new()->withWorkflowId("order-{$order->getId()}"),
                ),
                new OrderPizzaPayload($order->getId()),
            );

            return $order;
        });

        return $this->json(['redirectUrl' => $this->generateUrl('customer_status', ['id' => $order->getId()])]);
    }

    #[Route('/status/{id}', name: 'customer_status')]
    public function status(int $id, OrderRepository $orders): Response
    {
        $order = $orders->find($id) ?? throw $this->createNotFoundException();

        return $this->render('customer/status.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/order/{id}/payment', name: 'customer_payment', methods: ['POST'])]
    public function payment(int $id, Request $request, OrderRepository $orders): Response
    {
        $orders->find($id) ?? throw $this->createNotFoundException();

        $status = $request->request->getString('status');
        if (!in_array($status, ['SUCCESS', 'DECLINED'], true)) {
            throw new BadRequestHttpException('Invalid Status');
        }

        $workflow = $this->workflowClient->newRunningWorkflowStub(HandlePizzaOrder::class, "order-$id");
        $workflow->payment(['status' => $status]);

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        return $this->redirectToRoute('customer_status', ['id' => $id]);
    }
}
