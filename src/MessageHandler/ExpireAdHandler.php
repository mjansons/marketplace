<?php

namespace App\MessageHandler;

use App\Entity\BaseProduct;
use App\Message\ExpireAdMessage;
use App\Service\ProductWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ExpireAdHandler
{
    private EntityManagerInterface $entityManager;
    private ProductWorkflowService $workflowService;

    public function __construct(EntityManagerInterface $entityManager, ProductWorkflowService $workflowService)
    {
        $this->entityManager = $entityManager;
        $this->workflowService = $workflowService;
    }

    public function __invoke(ExpireAdMessage $message): void
    {
        $ad = $this->entityManager
            ->getRepository(BaseProduct::class)
            ->find($message->getAdId());

        if (!$ad || $ad->getStatus() !== 'published') {
            return;
        }

        if ($ad->getExpiryDate() <= new \DateTime()) {
            $this->workflowService->applyTransition($ad, 'expire');
            $this->entityManager->flush();
        }
    }
}
