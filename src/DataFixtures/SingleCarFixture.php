<?php

namespace App\DataFixtures;

use App\Entity\Car;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SingleCarFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1) Retrieve the basic user
        $userRepo = $manager->getRepository(User::class);
        $user = $userRepo->findOneBy(['email' => 'basicuser@basicuser.basicuser']);

        if (!$user) {
            throw new \RuntimeException('UserFixtures must run first to create "basicuser@basicuser.basicuser"!');
        }

        // 2) Create exactly one Car
        $car = (new Car())
            ->setTitle("Bmw 3 Series (2020)")
            ->setDescription("Car description for Bmw 3 Series, volume 2.0.")
            ->setPrice(10000)
            ->setBrand("Bmw")
            ->setModel("3 Series")
            ->setYear(2020)
            ->setVolume(2.0)
            ->setRun(50000)
            ->setUser($user)
            ->setStatus('draft');  // starts as draft

        $manager->persist($car);
        $manager->flush();
    }
}
