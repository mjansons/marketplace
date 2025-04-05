<?php

namespace App\Tests\Unit;

use App\Entity\Car;
use App\Entity\User;
use App\Message\ExpireAdMessage;
use App\Service\ProductFormHandler;
use App\Service\ProductImageHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class ProductFormHandlerTest extends TestCase
{
    /**
     * @throws Exception
     * @throws ExceptionInterface|\DateMalformedStringException
     */
    public function testHandleProductForm(): void
    {
        // Step 1: mock the dependencies
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $imageHandler = $this->createMock(ProductImageHandler::class);
        $bus = $this->createMock(MessageBusInterface::class);

        // Step 2: instantiate the service with mocked dependencies
        $formHandler = new ProductFormHandler($entityManager, $imageHandler, $bus);

        // Step 3: create a Car instance with status and expiry date
        $car = new Car();
        $car->setStatus('published');
        $car->setExpiryDate((new DateTime())->modify('+1 day'));

        // Step 4: inject a fake ID into the private `id` property of BaseProduct
        $reflection = new \ReflectionClass(\App\Entity\BaseProduct::class);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($car, 1);

        // Step 5: simulate the image field of the form
        $imageField = $this->createMock(FormInterface::class);
        $imageField->method('getData')->willReturn([]); // no files uploaded

        $form = $this->createMock(FormInterface::class);
        $form->method('get')->with('imageFiles')->willReturn($imageField);

        // Step 6: fake image handler processing
        $imageHandler->method('processUploads')->willReturn([]);
        $imageHandler->method('processRemovals')->willReturn([]);

        // Step 7: create a fake request
        $request = new Request([], ['removedImages' => json_encode([])]);

        // Step 8: use a real User entity, not a UserInterface mock
        $user = new User();

        // Step 9: expect DB persist/flush to be called
        $entityManager->expects($this->once())->method('persist')->with($car);
        $entityManager->expects($this->once())->method('flush');

        // Step 10: expect message bus to dispatch a delayed message
        $bus->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($message) use ($car) {
                    return $message instanceof ExpireAdMessage && $message->getAdId() === $car->getId();
                }),
                $this->callback(function ($stamps) {
                    return isset($stamps[0]) && $stamps[0] instanceof DelayStamp;
                })
            )
            ->willReturnCallback(fn($message, $stamps) => new Envelope($message, $stamps));

        // Step 11: run the actual handler
        $formHandler->handleProductForm($form, $request, $car, $user);

        // Step 12: verify post-conditions
        $this->assertInstanceOf(DateTime::class, $car->getPublishDate());
        $this->assertSame($user, $car->getUser());
    }
}
