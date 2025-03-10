<?php

namespace App\DataFixtures;

use App\Entity\Gender;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class GenderFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $genders = ['Male', 'Female', 'Non-binary', 'Other', 'Prefer not to say'];

        foreach ($genders as $genderName) {
            $gender = new Gender();
            $gender->setName($genderName);

            $manager->persist($gender);
        }
        $manager->flush();
    }
}
