<?php

namespace App\Tests\MessageHandlerTests;

use App\DataFixtures\UserFixtures;
use App\Entity\Car;
use App\Entity\User;
use App\Message\ExpireAdMessage;
use App\MessageHandler\ExpireAdHandler;
use App\Service\ProductWorkflowService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ExpireAdHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ProductWorkflowService $workflowService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->workflowService = $container->get(ProductWorkflowService::class);

        $databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $databaseTool->loadFixtures([
            UserFixtures::class,
        ]);
    }

    /**
     * @throws ORMException
     */
    public function testAdExpiration(): void
    {
        // Retrieve an owner from the database (loaded via UserFixtures)
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => 'basicuser@basicuser.basicuser']);
        $this->assertNotNull($user, 'The basic user should be loaded from fixtures.');

        // Create a Car instance that is "published" with an expiry date already passed.
        $car = new Car();
        $car->setTitle('Test Car');
        $car->setDescription('Test description');
        $car->setPrice(10000);
        $car->setBrand('Bmw');
        $car->setModel('Test Model');
        $car->setYear(2020);
        $car->setVolume(2.0);
        $car->setRun(50000);

        // Set the dates so that the ad is already expired.
        $car->setPublishDate(new DateTime('-10 days'));
        $car->setExpiryDate(new DateTime('-1 day'));
        $car->setStatus('published'); // set state to published.
        $car->setUser($user); // assign the owner

        // Save the product to the database.
        $this->em->persist($car);
        $this->em->flush();

        // Create the ExpireAdMessage with the ID of the car.
        $message = new ExpireAdMessage($car->getId());

        // Instantiate the message handler.
        $handler = new ExpireAdHandler($this->em, $this->workflowService);

        // Manually invoke the message handler.
        $handler->__invoke($message);

        // Refresh the product from the database to get any changes.
        $this->em->refresh($car);

        // Assert that the product status is now "expired".
        $this->assertEquals(
            'expired',
            $car->getStatus(),
            'The car status should be "expired" after processing ExpireAdMessage.'
        );
    }
}
