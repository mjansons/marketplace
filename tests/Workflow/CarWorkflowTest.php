<?php

namespace App\Tests\Workflow;

use App\Entity\Car;
use App\Service\ProductWorkflowService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CarWorkflowTest extends KernelTestCase
{
    private ProductWorkflowService $workflowService;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->workflowService = self::getContainer()->get(ProductWorkflowService::class);
    }

    private function createCar(): Car
    {
        return (new Car())
            ->setTitle('Test Car')
            ->setDescription('This is a test car.')
            ->setPrice(15000)
            ->setYear(2022)
            ->setVolume(2.0)
            ->setBrand('Toyota')
            ->setModel('Camry')
            ->setRun(5000);
    }

    public function testInitialState(): void
    {
        $car = $this->createCar();
        $this->assertSame('draft', $car->getStatus());
    }

    public function testPublishTransition(): void
    {
        $car = $this->createCar();

        $this->workflowService->applyTransition($car, 'publish');
        $this->assertSame('published', $car->getStatus());
    }

    public function testExpireTransition(): void
    {
        $car = $this->createCar();
        $this->workflowService->applyTransition($car, 'publish');
        $this->workflowService->applyTransition($car, 'expire');

        $this->assertSame('expired', $car->getStatus());
    }

    public function testRePublishTransition(): void
    {
        $car = $this->createCar();
        $this->workflowService->applyTransition($car, 'publish');
        $this->workflowService->applyTransition($car, 'expire');
        $this->workflowService->applyTransition($car, 're_publish');

        $this->assertSame('published', $car->getStatus());
    }

    public function testInvalidTransitionThrowsException(): void
    {
        $car = $this->createCar();

        $this->expectException(\LogicException::class);
        $this->workflowService->applyTransition($car, 'expire'); // Invalid transition
    }
}
