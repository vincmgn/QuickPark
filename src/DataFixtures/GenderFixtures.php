<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Gender;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

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
