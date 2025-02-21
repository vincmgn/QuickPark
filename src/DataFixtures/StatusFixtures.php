<?php

namespace App\DataFixtures;

use App\Entity\Status;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class StatusFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $statuses = ['Verified', 'Pending', 'Rejected', 'Approved', 'Cancelled', 'Confirmed', 'Completed'];

        foreach ($statuses as $statusName) {
            $status = new Status();
            $status->setName($statusName);

            $now = new \DateTime();
            $status->setCreatedAt($now);
            $status->setUpdatedAt($now);
            
            $manager->persist($status);
        }
        $manager->flush();
    }
}
