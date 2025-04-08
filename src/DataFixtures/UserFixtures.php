<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        // Regular user
        $user = new User();
        $user->setEmail('basicuser@basicuser.basicuser');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'basicuser'));
        $user->setRoles(['ROLE_USER']);
        $manager->persist($user);

        $this->addReference('user_regular', $user);
        // Admin user
        $admin = new User();
        $admin->setEmail('adminuser@adminuser.adminuser');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'adminuser'));
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        $this->addReference('user_admin', $admin);
        $manager->flush();

    }
}

