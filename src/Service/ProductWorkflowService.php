<?php

namespace App\Service;

use App\Entity\BaseProduct;
use Symfony\Component\Workflow\WorkflowInterface;

class ProductWorkflowService
{
    private WorkflowInterface $workflow;

    public function __construct(WorkflowInterface $blogPostStateMachine)
    {
        $this->workflow = $blogPostStateMachine;

    }

    public function applyTransition(BaseProduct $product, string $transition): void
    {
        if ($this->workflow->can($product, $transition)) {
            $this->workflow->apply($product, $transition);
        } else {
            throw new \LogicException(sprintf('Cannot apply transition "%s" from state "%s".', $transition, $product->getStatus()));
        }
    }
}