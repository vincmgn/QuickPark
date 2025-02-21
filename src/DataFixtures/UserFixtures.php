<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // public user
        $publicUser = new User();
        $publicUser->setRoles(['ROLE_USER']);
        $publicUser->setPassword($this->userPasswordHasher->hashPassword($publicUser, 'password'));
        $manager->persist($publicUser);

        // admin user
        $adminUser = new User();
        $adminUser->setRoles(['ROLE_ADMIN']);
        $adminUser->setPassword($this->userPasswordHasher->hashPassword($adminUser, 'password'));
        $manager->persist($adminUser);

        $manager->flush();
    }
}
