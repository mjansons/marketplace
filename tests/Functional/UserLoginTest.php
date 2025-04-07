<?php

namespace App\Tests\Functional;

use App\DataFixtures\UserFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

class UserLoginTest extends WebTestCase
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
    public function testUserCanLogin(): void
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

        $crawler = $this->client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a:contains("log out")');
    }

}
