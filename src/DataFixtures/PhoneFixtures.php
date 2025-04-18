<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Phone;
use App\Repository\UserRepository;
use App\Repository\StatusRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class PhoneFixtures extends Fixture implements DependentFixtureInterface
{
    private StatusRepository $statusRepository;

    public function __construct(StatusRepository $statusRepository)
    {
        $this->statusRepository = $statusRepository;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $statuses = $this->statusRepository->findAll();
        for ($i = 0; $i < 10; $i++) {
            $randomStatus = $statuses[array_rand($statuses)];
            $user = $this->getReference('user_' . $i, User::class);

            $phone = new Phone();
            $phone->setNumber($faker->phoneNumber);
            $phone->setStatus($randomStatus);
            $phone->setOwner($user);

            $now = new \DateTime();
            $phone->setCreatedAt($now);
            $phone->setUpdatedAt($now);

            $manager->persist($phone);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            StatusFixtures::class,
            UserFixtures::class
        ];
    }
}
