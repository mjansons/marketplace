<?php

namespace App\Tests\Functional;

use App\DataFixtures\UserFixtures;
use App\DataFixtures\SingleCarFixture;
use App\Entity\Car;
use App\Entity\User;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

class DeleteCarAdTest extends WebTestCase
{
    protected AbstractDatabaseTool $databaseTool;
    protected KernelBrowser $client;
    protected ReferenceRepository $referenceRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        // Load the user & single car fixtures
        $this->databaseTool = static::getContainer()
            ->get(DatabaseToolCollection::class)
            ->get();

        $fixturesExecutor = $this->databaseTool->loadFixtures([
            UserFixtures::class,
            SingleCarFixture::class,
        ]);

        // Reference repository to fetch your fixture objects
        $this->referenceRepo = $fixturesExecutor->getReferenceRepository();
    }

    public function testDeleteCarFromEditPage(): void
    {
        // 1) Fetch the user and car from the fixtures
        /** @var User $user */
        $user = $this->referenceRepo->getReference('user_regular', User::class);
        /** @var Car $car */
        $car = $this->referenceRepo->getReference('car_single', Car::class);
        $carId = $car->getId();

        // 2) Log in as that user
        $this->client->loginUser($user);

        // 3) Visit the edit page directly
        $crawler = $this->client->request('GET', "/product/edit/{$carId}");
        $this->assertResponseIsSuccessful();

        // 4) Find and submit the delete form
        // The button has text "delete", so we can do:
        $this->client->submitForm('delete');
        // In a real browser, this would trigger the JavaScript "Are you sure?"
        // but BrowserKit doesn't run JS, so it just submits the form.

        // 5) Follow the redirect (if your controller redirects after delete)
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // 6) Confirm that the car no longer appears on the dashboard
        $crawler = $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();

        // The original fixture used "Bmw 3 Series (2020)" as the car title
        $this->assertStringNotContainsString(
            'Bmw 3 Series (2020)',
            $this->client->getResponse()->getContent(),
            'Car should be deleted and not visible on the dashboard anymore!'
        );
    }
}