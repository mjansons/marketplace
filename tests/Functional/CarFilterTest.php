<?php

namespace App\Tests\Functional;

use App\DataFixtures\SingleCarFixture;
use App\DataFixtures\UserFixtures;
use App\Entity\Car;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CarFilterTest extends WebTestCase
{
    protected AbstractDatabaseTool $databaseTool;
    protected KernelBrowser $client;
    protected $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->databaseTool = static::getContainer()
            ->get(DatabaseToolCollection::class)
            ->get();
        $this->databaseTool->loadFixtures([
            UserFixtures::class,
            SingleCarFixture::class,
        ]);

        $this->em = static::getContainer()->get('doctrine')->getManager();

        // Publish the car from the fixture.
        $car = $this->em->getRepository(Car::class)->findOneBy(['title' => 'Bmw 3 Series (2020)']);
        $car->setStatus('published');
        $this->em->flush();
        $this->em->refresh($car);
    }

    /**
     * Utility method to get the text of the response.
     */
    private function getResultsText(): string
    {
        return $this->client->getResponse()->getContent();
    }

    public function testFilterByModel_Matching(): void
    {
        // The car's model is "3 Series", so filtering by that model should match.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '3 Series',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('3 Series', $this->getResultsText());
    }

    public function testFilterByModel_NonMatching(): void
    {
        // Filtering for a model that does not match (e.g. "A3") should yield no results.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => 'A3',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No results found', $this->getResultsText());
    }

    public function testFilterByMinYear(): void
    {
        // With minYear = 2020 (the car's year), the car should appear.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '2020',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('3 Series', $this->getResultsText());

        // With minYear = 2021, the car should be excluded.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '2021',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No results found', $this->getResultsText());
    }

    public function testFilterByMaxYear(): void
    {
        // With maxYear = 2020, the car (year 2020) should appear.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '2020',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('3 Series', $this->getResultsText());

        // With maxYear = 2019, the car should be excluded.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '2019',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No results found', $this->getResultsText());
    }

    public function testFilterByMinVolume(): void
    {
        // The car has volume 2.0; with minVolume = 2.0 it should appear.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '2.0',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('3 Series', $this->getResultsText());

        // With minVolume = 2.1, the car should be excluded.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '2.1',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No results found', $this->getResultsText());
    }

    public function testFilterByMaxVolume(): void
    {
        // With maxVolume = 2.0, the car should appear.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '2.0',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('3 Series', $this->getResultsText());

        // With maxVolume = 1.9, the car should be excluded.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '1.9',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No results found', $this->getResultsText());
    }

    public function testFilterByMinRun(): void
    {
        // The car's run is 50000 km; with minRun = 50000 it should appear.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '50000',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('3 Series', $this->getResultsText());

        // With minRun = 50001, it should be excluded.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '50001',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No results found', $this->getResultsText());
    }

    public function testFilterByMaxRun(): void
    {
        // With maxRun = 50000, the car should appear.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '50000',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('3 Series', $this->getResultsText());

        // With maxRun = 49999, the car should be excluded.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '49999',
            'minPrice'  => '',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No results found', $this->getResultsText());
    }

    public function testFilterByMinPrice(): void
    {
        // The car's price is 10000; with minPrice = 10000 it should appear.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '10000',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('3 Series', $this->getResultsText());

        // With minPrice = 10001, it should be excluded.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '10001',
            'maxPrice'  => '',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No results found', $this->getResultsText());
    }

    public function testFilterByMaxPrice(): void
    {
        // With maxPrice = 10000, the car should appear.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '10000',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('3 Series', $this->getResultsText());

        // With maxPrice = 9999, the car should be excluded.
        $this->client->request('GET', '/car/filter/bmw', [
            'model'     => '',
            'minYear'   => '',
            'maxYear'   => '',
            'minVolume' => '',
            'maxVolume' => '',
            'minRun'    => '',
            'maxRun'    => '',
            'minPrice'  => '',
            'maxPrice'  => '9999',
            'sort'      => 'year',
            'direction' => 'DESC',
            'page'      => 1,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No results found', $this->getResultsText());
    }
}
