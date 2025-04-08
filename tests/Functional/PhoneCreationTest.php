<?php

namespace App\Tests\Functional;

use App\DataFixtures\UserFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

class PhoneCreationTest extends WebTestCase
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

    public function testUserCanCreateNewPhone(): void
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

        // 2) Go to the 'create a new Phone ad' page
        $crawler = $this->client->request('GET', '/product/new/phone');
        $this->assertResponseIsSuccessful();

        // 3) Fill in form
        $form = $crawler->selectButton('save')->form();
        $form->disableValidation();

        $form->setValues([
            'phone[title]'            => 'My Test Phone',
            'phone[description]'      => 'A sleek new phone.',
            'phone[price]'            => 799,
            'phone[brand]'            => 'Samsung',
            'phone[model]'            => 'Galaxy S21',
            'phone[memory]'           => 128,
            'phone[productCondition]' => 'new',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 4) Check dashboard
        $crawler = $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3:contains("Drafts") + ul', 'My Test Phone');
    }
}
