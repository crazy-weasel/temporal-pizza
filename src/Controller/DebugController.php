<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Temporal\Api\Workflowservice\V1\DescribeNamespaceRequest;
use Temporal\Client\WorkflowClient;
use Temporal\Client\WorkflowClientInterface;

#[Route('/debug')]
class DebugController extends AbstractController
{
    public function __construct(private readonly WorkflowClientInterface $workflowClient)
    {
    }

    #[Route('/temporal', name: 'debug_temporal')]
    public function temporal(): JsonResponse
    {
        assert($this->workflowClient instanceof WorkflowClient);

        try {
            $response = $this->workflowClient->getServiceClient()->DescribeNamespace(
                (new DescribeNamespaceRequest())->setNamespace('default'),
            );

            return $this->json([
                'status' => 'ok',
                'namespace' => $response->getNamespaceInfo()->getName(),
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
