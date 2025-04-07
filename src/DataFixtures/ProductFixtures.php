<?php

namespace App\DataFixtures;

use App\Entity\Car;
use App\Entity\Phone;
use App\Entity\Computer;
use App\Entity\User;
use App\Service\CarData;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Random\RandomException;

class ProductFixtures extends Fixture
{
    /**
     * @throws RandomException
     */
    public function load(ObjectManager $manager): void
    {
        $userRepo = $manager->getRepository(User::class);
        $user = $userRepo->findOneBy(['email' => 'basicuser@basicuser.basicuser']);

        if (!$user) {
            throw new \RuntimeException('Basic user not found. Make sure UserFixtures ran first.');
        }

        $defaultStatus = 'draft';

        // Generate 20 Cars
        for ($i = 0; $i < 20; $i++) {
            $brand = $this->randomElement(CarData::getCarBrands());
            $model = $this->randomElement(CarData::getModelsByBrand($brand));
            $volume = $this->randomElement(CarData::getVolume());
            $year = random_int(2000, (int)date('Y'));

            $car = (new Car())
                ->setTitle("$brand $model ($year)")
                ->setDescription("Car description for $brand $model, volume $volume.")
                ->setPrice(random_int(5000, 30000))
                ->setBrand($brand)
                ->setModel($model)
                ->setYear($year)
                ->setVolume($volume)
                ->setRun(random_int(5000, 200000))
                ->setUser($user)
                ->setStatus($defaultStatus);

            $manager->persist($car);
        }

        // Generate 20 Computers
        $computerBrands = ['Apple', 'Dell', 'HP', 'Lenovo'];
        $computerConditions = ['New', 'Used', 'Refurbished'];

        for ($i = 0; $i < 20; $i++) {
            $brand = $this->randomElement($computerBrands);
            $model = "Model-" . strtoupper(substr(md5(uniqid()), 0, 5));

            $computer = (new Computer())
                ->setTitle("$brand $model")
                ->setDescription("Computer description for $brand $model.")
                ->setPrice(random_int(300, 2500))
                ->setBrand($brand)
                ->setModel($model)
                ->setRam(random_int(4, 64))
                ->setStorage(random_int(128, 2048))
                ->setProductCondition($this->randomElement($computerConditions))
                ->setUser($user)
                ->setStatus($defaultStatus);

            $manager->persist($computer);
        }

        // Generate 20 Phones
        $phoneBrands = ['Apple', 'Samsung', 'Google', 'OnePlus'];
        $phoneConditions = ['New', 'Used', 'Refurbished'];

        for ($i = 0; $i < 20; $i++) {
            $brand = $this->randomElement($phoneBrands);
            $model = "Model-" . strtoupper(substr(md5(uniqid()), 0, 5));

            $phone = (new Phone())
                ->setTitle("$brand $model")
                ->setDescription("Phone description for $brand $model.")
                ->setPrice(random_int(200, 1500))
                ->setBrand($brand)
                ->setModel($model)
                ->setMemory(random_int(32, 512))
                ->setProductCondition($this->randomElement($phoneConditions))
                ->setUser($user)
                ->setStatus($defaultStatus);

            $manager->persist($phone);
        }

        $manager->flush();
    }

    private function randomElement(array $array): mixed
    {
        return $array[array_rand($array)];
    }
}
