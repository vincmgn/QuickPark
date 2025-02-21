<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Email;
use App\Repository\StatusRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class EmailFixtures extends Fixture implements DependentFixtureInterface
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

            $email = new Email();
            $email->setEmail($faker->email);
            $email->setStatus($randomStatus);

            $now = new \DateTime();
            $email->setCreatedAt($now);
            $email->setUpdatedAt($now);

            $manager->persist($email);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            StatusFixtures::class,
        ];
    }
}
