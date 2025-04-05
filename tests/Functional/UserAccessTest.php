<?php

namespace App\Tests\Functional;

use App\DataFixtures\UserFixtures;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

class UserAccessTest extends WebTestCase
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
    }

    public function testHomepageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertSelectorExists('a:contains("home")');
        $this->assertSelectorExists('a:contains("log in")');
        $this->assertSelectorExists('a:contains("sign up")');
    }


    public function testLoginPageIsAccessible(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testRegisterPageIsAccessible(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testRegularUserDashboardAccess(): void
    {
        $this->databaseTool->loadFixtures([UserFixtures::class]);

        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'basicuser@basicuser.basicuser',
            'password' => 'basicuser',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Categories');
    }

    public function testRegularUserCannotAccessAdmin(): void
    {
        $this->databaseTool->loadFixtures([UserFixtures::class]);

        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'basicuser@basicuser.basicuser',
            'password' => 'basicuser',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->client->request('GET', '/admin');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminUserCanAccessAdmin(): void
    {
        $this->databaseTool->loadFixtures([UserFixtures::class]);

        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'adminuser@adminuser.adminuser',
            'password' => 'adminuser',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }
}
