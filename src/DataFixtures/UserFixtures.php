<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
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

        $filteredGenders = array_filter($genders, fn($gender) => $gender->getName() === 'Male');
        $maleGender = reset($filteredGenders);

        $adminUser = new User();
        $adminUser->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $adminUser->setPassword($this->userPasswordHasher->hashPassword($adminUser, 'admin'));
        $adminUser->setUsername('alexnbl27');
        $adminUser->setGender($maleGender);
        $adminUser->setCreatedAt($now);
        $adminUser->setUpdatedAt($now);
        $manager->persist($adminUser);
        $this->addReference('alexnbl27', $adminUser);

        $adminUser = new User();
        $adminUser->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $adminUser->setPassword($this->userPasswordHasher->hashPassword($adminUser, 'admin'));
        $adminUser->setUsername('vinvin');
        $adminUser->setGender($maleGender);
        $adminUser->setCreatedAt($now);
        $adminUser->setUpdatedAt($now);
        $manager->persist($adminUser);
        $this->addReference('vinvin', $adminUser);

        $demoAdminUser = new User();
        $demoAdminUser->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $demoAdminUser->setPassword($this->userPasswordHasher->hashPassword($demoAdminUser, 'password'));
        $demoAdminUser->setUsername('adminDemo');
        $demoAdminUser->setGender($maleGender);
        $demoAdminUser->setCreatedAt($now);
        $demoAdminUser->setUpdatedAt($now);
        $manager->persist($demoAdminUser);
        $this->addReference('adminDemo', $demoAdminUser);

        $demoUser = new User();
        $demoUser->setRoles(['ROLE_USER']);
        $demoUser->setPassword($this->userPasswordHasher->hashPassword($demoUser, 'password'));
        $demoUser->setUsername('userDemo');
        $demoUser->setGender($maleGender);
        $demoUser->setCreatedAt($now);
        $demoUser->setUpdatedAt($now);
        $manager->persist($demoUser);
        $this->addReference('userDemo', $demoUser);

        $manager->flush(); 
    }


    public function getDependencies(): array
    {
        return [
            GenderFixtures::class,
        ];
    }
}
