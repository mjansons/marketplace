<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

class UserRegistrationTest extends WebTestCase
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

    public function testUserCanRegister(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $form = $crawler->selectButton('register')->form([
            'registration_form[email]' => 'newuser@example.com',
            'registration_form[plainPassword][first]' => 'newuserpassword',
            'registration_form[plainPassword][second]' => 'newuserpassword',
            'registration_form[agreeTerms]' => '1',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $crawler = $this->client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a:contains("log out")');
    }



}
