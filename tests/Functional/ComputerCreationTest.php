<?php

namespace App\Tests\Functional;

use App\DataFixtures\UserFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

class ComputerCreationTest extends WebTestCase
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

    public function testUserCanCreateNewComputer(): void
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

        // 2) Go to the 'create a new Computer ad' page
        $crawler = $this->client->request('GET', '/product/new/computer');
        $this->assertResponseIsSuccessful();

        // 3) Fill in form
        $form = $crawler->selectButton('save')->form();
        $form->disableValidation(); // bypass HTML5 validation

        $form->setValues([
            'computer[title]'            => 'My Test Computer',
            'computer[description]'      => 'A fast and sleek test computer.',
            'computer[price]'            => 1500,
            'computer[brand]'            => 'Apple',
            'computer[model]'            => 'MacBook Pro',
            'computer[ram]'              => 16,
            'computer[storage]'          => 512,
            'computer[productCondition]' => 'new',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 4) Check it's in the dashboard drafts
        $crawler = $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3:contains("Drafts") + ul', 'My Test Computer');
    }
}
