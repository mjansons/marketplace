<?php

namespace App\Tests\Functional;

use App\DataFixtures\UserFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

class CarCreationTest extends WebTestCase
{
    protected AbstractDatabaseTool $databaseTool;
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $this->databaseTool = static::getContainer()
            ->get(DatabaseToolCollection::class)
            ->get();
        $this->databaseTool->loadFixtures([
            UserFixtures::class,
        ]);
    }

    public function testUserCanCreateNewCar(): void
    {
        // 1) Log in
        $crawler = $this->client->request('GET', '/login');
        $loginForm = $crawler->selectButton('Sign in')->form([
            'email'    => 'basicuser@basicuser.basicuser',
            'password' => 'basicuser',
        ]);
        $this->client->submit($loginForm);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 2) Go to the 'create a new Car ad' page
        $crawler = $this->client->request('GET', '/product/new/car');
        $this->assertResponseIsSuccessful();

        // 3) Prepare the form and disable validation for JS-populated fields
        $form = $crawler->selectButton('save')->form();
        $form->disableValidation(); // bypass strict HTML field validation

        $form->setValues([
            'car[title]'       => 'My Test Car',
            'car[description]' => 'A lovely test car created by a functional test.',
            'car[price]'       => 9999,
            'car[brand]'       => 'Audi',
            'car[model]'       => 'A3',
            'car[year]'        => '2020',
            'car[volume]'      => '2',
            'car[run]'         => '50000',
        ]);

        // 4) Submit the form
        $this->client->submit($form);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 5) Visit the dashboard to confirm car appears in drafts
        $crawler = $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3:contains("Drafts") + ul', 'My Test Car');
    }
}
