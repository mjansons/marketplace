<?php

namespace App\Tests\Functional;

use App\DataFixtures\UserFixtures;
use App\DataFixtures\SingleCarFixture;
use App\Entity\User;
use App\Entity\Car;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

class EditCarAdAndVerifyPublicViewTest extends WebTestCase
{
    protected AbstractDatabaseTool $databaseTool;
    protected KernelBrowser $client;
    protected ReferenceRepository $referenceRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $this->databaseTool = static::getContainer()
            ->get(DatabaseToolCollection::class)
            ->get();

        $fixturesExecutor = $this->databaseTool->loadFixtures([
            UserFixtures::class,
            SingleCarFixture::class,
        ]);

        $this->referenceRepo = $fixturesExecutor->getReferenceRepository();
    }

    public function testEditCarAdAndVerifyPublicView(): void
    {
        /** @var User $user */
        $user = $this->referenceRepo->getReference('user_regular', User::class);
        /** @var Car $car */
        $car = $this->referenceRepo->getReference('car_single', Car::class);
        $carId = $car->getId();

        $this->client->loginUser($user);

        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.detail-card .value', 'Bmw 3 Series (2020)');

        $crawler = $this->client->request('GET', "/product/edit/{$carId}");
        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('form[name="car"]');


        $this->client->submitForm('save changes', [
            'car[title]'       => 'New Title for BMW',
            'car[description]' => 'new description',
            'car[price]'       => 23,
            'car[brand]'       => 'Bmw',
            'car[model]'       => '3 Series',
            'car[year]'        => 2024,
            'car[volume]'      => 0.6,
            'car[run]'         => 2345,
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $crawler = $this->client->request('GET', "/car/{$carId}");
        $this->assertResponseIsSuccessful();

        // Select all nodes with ".detail-card .value" and get their text values
        $detailValues = $crawler->filter('.detail-card .value')->each(function($node) {
            return trim($node->text());
        });

        // Check that each expected value exists somewhere among the matched values
        $this->assertSelectorTextContains('h1', 'New Title for BMW');
        $this->assertContains('new description', $detailValues, 'Expected description "new description" not found.');
        $this->assertContains('23 €', $detailValues, 'Expected price "23 €" not found.');
        $this->assertContains('Bmw', $detailValues, 'Expected brand "Bmw" not found.');
        $this->assertContains('3 Series', $detailValues, 'Expected model "3 Series" not found.');
        $this->assertContains('2024', $detailValues, 'Expected year "2024" not found.');
        $this->assertContains('0.6', $detailValues, 'Expected volume "0.6" not found.');
        $this->assertContains('2345', $detailValues, 'Expected run "2345" not found.');
    }
}
