<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Task\HandlePizzaOrder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;
use Temporal\Client\WorkflowClientInterface;

#[Route('/restaurant')]
class RestaurantController extends AbstractController
{
    public function __construct(private readonly WorkflowClientInterface $workflowClient)
    {
    }

    #[Route('', name: 'restaurant')]
    public function index(OrderRepository $orders): Response
    {
        $active = $orders->createQueryBuilder('o')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', [
                OrderStatus::WaitingForRestaurant,
                OrderStatus::Baking,
                OrderStatus::WaitingForDriver,
                OrderStatus::Delivering,
            ])
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('restaurant/index.html.twig', [
            'orders' => $active,
        ]);
    }

    #[Route('/order/{id}/confirm', name: 'restaurant_confirm', methods: ['POST'])]
    public function confirm(int $id, Request $request, OrderRepository $orders): Response
    {
        $orders->find($id) ?? throw $this->createNotFoundException();

        $status = $request->request->getString('status');
        if (!in_array($status, ['ACCEPTED', 'DENIED'], true)) {
            throw new BadRequestHttpException('Invalid Status');
        }

        $workflow = $this->workflowClient->newRunningWorkflowStub(HandlePizzaOrder::class, "order-$id");
        $workflow->restaurantConfirmation(['status' => $status]);

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        return $this->redirectToRoute('restaurant');
    }

    #[Route('/order/{id}/assign-driver', name: 'restaurant_assign_driver', methods: ['POST'])]
    public function assignDriver(int $id, Request $request, OrderRepository $orders): Response
    {
        $orders->find($id) ?? throw $this->createNotFoundException();

        $driverName = $request->request->getString('driverName', 'Mario');

        $workflow = $this->workflowClient->newRunningWorkflowStub(HandlePizzaOrder::class, "order-{$id}");
        $workflow->driverAssigned(['driverName' => $driverName]);

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        return $this->redirectToRoute('restaurant');
    }

    #[Route('/order/{id}/deliver', name: 'restaurant_deliver', methods: ['POST'])]
    public function deliver(int $id, Request $request, OrderRepository $orders): Response
    {
        $orders->find($id) ?? throw $this->createNotFoundException();

        $workflow = $this->workflowClient->newRunningWorkflowStub(HandlePizzaOrder::class, "order-{$id}");
        $workflow->delivered();

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        return $this->redirectToRoute('restaurant');
    }
}
