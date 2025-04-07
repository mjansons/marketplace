<?php

namespace App\Tests\Functional;

use App\DataFixtures\ProductFixtures;
use App\DataFixtures\UserFixtures;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

class ProductCreateTest extends WebTestCase
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
            ProductFixtures::class,
        ]);
    }

    public function testUserCanPublishAndUnpublishProduct(): void
    {
        // 1) Login as basic user
        $crawler = $this->client->request('GET', '/login');
        $loginForm = $crawler->selectButton('Sign in')->form([
            'email'    => 'basicuser@basicuser.basicuser',
            'password' => 'basicuser',
        ]);
        $this->client->submit($loginForm);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 2) Go to /dashboard => see all draft products
        $crawler = $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();

        // 3) Publish a product from the draft list
        // find the first publish form under <h3>Drafts</h3>
        $draftPublishForm = $crawler->filter('h3:contains("Drafts") + ul form[action^="/product/publish"]')->first();
        // This locates the first <form> inside the <ul> that follows the <h3> titled "Drafts"
        // Specifically, it's looking for a form with an action like /product/publish/{id}

        $this->assertGreaterThan(0, $draftPublishForm->count(), 'No draft publish form found!');
        // Ensures that such a form was actually found — otherwise the test fails

        // fill the publish form
        $publishForm = $draftPublishForm->form([
            'durationWeeks' => '2', // Select "2 weeks" from the dropdown
        ]);

        // Submit & follow
        $this->client->submit($publishForm);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 4) Now it should show in “Active”
        $crawler = $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        // find the first unpublish form under Active
        $activeUnpublishForm = $crawler->filter('h3:contains("Active") + ul form[action^="/product/unpublish"]')->first();
        $this->assertGreaterThan(0, $activeUnpublishForm->count(), 'No unpublish form found in Active section after publishing!');

        // 5) Unpublish the newly published product
        $unpublishForm = $activeUnpublishForm->form();
        $this->client->submit($unpublishForm);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 6) Check it's back in draft (or maybe it disappears from Active)
        $crawler = $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();

        // We can confirm there's at least one publish form in “Drafts” again
        $this->assertSelectorExists('h3:contains("Drafts") + ul form[action^="/product/publish"]');
    }
}
