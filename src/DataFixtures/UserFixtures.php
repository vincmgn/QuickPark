<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Traits\DataStatus;
use App\Repository\GenderRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    private $userPasswordHasher;
    private $genderRepository;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher, GenderRepository $genderRepository)
    {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->genderRepository = $genderRepository;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $now = new \DateTime();
        $genders = $this->genderRepository->findAll();

        for ($i = 0; $i < 100; $i++) {
            $user = new User();
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->userPasswordHasher->hashPassword($user, 'password'));
            $user->setUsername($faker->userName);
            $user->setGender($genders[array_rand($genders)]);
            $user->setCreatedAt($now);
            $user->setUpdatedAt($now);
            $manager->persist($user);
            $this->addReference('user_' . $i, $user);
        }

        $adminUser = new User();
        $adminUser->setRoles(['ROLE_ADMIN']);
        $adminUser->setPassword($this->userPasswordHasher->hashPassword($adminUser, 'password'));
        $adminUser->setUsername('admin');
        $adminUser->setGender($genders[array_rand($genders)]);
        $adminUser->setCreatedAt($now);
        $adminUser->setUpdatedAt($now);
        $manager->persist($adminUser);
        $this->addReference('admin', $adminUser);

        $manager->flush(); 
    }


    public function getDependencies(): array
    {
        return [
            GenderFixtures::class,
        ];
    }
}
