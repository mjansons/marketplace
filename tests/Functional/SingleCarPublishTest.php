<?php

namespace App\Tests\Functional;

use App\DataFixtures\UserFixtures;
use App\DataFixtures\SingleCarFixture;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

class SingleCarPublishTest extends WebTestCase
{
    protected AbstractDatabaseTool $databaseTool;
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        // Load user + single Car fixture
        $this->databaseTool = static::getContainer()
            ->get(DatabaseToolCollection::class)
            ->get();

        $this->databaseTool->loadFixtures([
            UserFixtures::class,
            SingleCarFixture::class,
        ]);
    }

    public function testPublishCarAndCheckListing(): void
    {
        // 1) Log in with basic user
        $crawler = $this->client->request('GET', '/login');
        $loginForm = $crawler->selectButton('Sign in')->form([
            'email'    => 'basicuser@basicuser.basicuser',
            'password' => 'basicuser',
        ]);
        $this->client->submit($loginForm);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 2) Visit dashboard => see 1 draft car
        $crawler = $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();

        // We expect exactly one publish form in the "Drafts" area
        $draftPublishForm = $crawler->filter('h3:contains("Drafts") + ul form[action^="/product/publish"]')->first();
        $this->assertGreaterThan(
            0,
            $draftPublishForm->count(),
            'Expected to find the single-car draft publish form!'
        );

        // 3) Submit "publish" with 2 weeks duration
        $publishForm = $draftPublishForm->form([
            'durationWeeks' => '2',
        ]);
        $this->client->submit($publishForm);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 4) Check it's now in "Active"
        $crawler = $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();

        $activeUnpublishForm = $crawler->filter('h3:contains("Active") + ul form[action^="/product/unpublish"]')->first();
        $this->assertGreaterThan(
            0,
            $activeUnpublishForm->count(),
            'Expected the newly published car in Active section!'
        );

        // 5) Now confirm brand link in /car/cars/select-brand
        $crawler = $this->client->request('GET', '/car/cars/select-brand');
        $this->assertResponseIsSuccessful();

        // We know brand is "Bmw"
        $this->assertSelectorTextContains('ul', 'Bmw (1)');

        // 6) Next, check the brand listing => /car/filter/Bmw
        $crawler = $this->client->request('GET', '/car/filter/Bmw');
        $this->assertResponseIsSuccessful();

        // Confirm table has the row with "3 Series"
        $this->assertStringContainsString(
            '3 Series',
            $crawler->filter('table')->text()
        );

        // 7) Unpublish again
        $crawler = $this->client->request('GET', '/dashboard');
        $unpublishForm = $crawler->filter('h3:contains("Active") + ul form[action^="/product/unpublish"]')->first()->form();
        $this->client->submit($unpublishForm);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 8) Confirm it’s back in Drafts
        $crawler = $this->client->request('GET', '/dashboard');
        $this->assertSelectorExists('h3:contains("Drafts") + ul form[action^="/product/publish"]');

        // 9) Confirm Bmw is no longer listed
        $crawler = $this->client->request('GET', '/car/cars/select-brand');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('a:contains("Bmw (")');
    }
}
