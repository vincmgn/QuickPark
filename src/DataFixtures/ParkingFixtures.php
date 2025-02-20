<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Parking;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;

class ParkingFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        for ($i = 0; $i < 100; $i++) {
            $parking = new Parking();
            $parking->setName($faker->name);
            $parking->setDescription($faker->text);

            // Générer des coordonnées valides pour la géolocalisation
            $latitude = $faker->latitude(-90, 90);
            $longitude = $faker->longitude(-90, 90);
            $location = new Point($latitude, $longitude);
            $parking->setLocation($location);

            $parking->setIsEnabled($faker->boolean);
            $manager->persist($parking);

            $now = new \DateTime();
            $parking->setCreatedAt($now);
            $parking->setUpdatedAt($now);
        }
        $manager->flush();
    }
}
