<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Parking;
use App\Repository\UserRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ParkingFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        for ($i = 0; $i < 100; $i++) {
            $user = $this->getReference('user_' . $i, User::class);

            $parking = new Parking();
            $parking->setName($faker->text(50));
            $parking->setDescription($faker->text);
            $parking->setIsEnabled($faker->boolean);
            $parking->setOwner($user);

            // Générer des coordonnées valides pour la géolocalisation
            $latitude = $faker->latitude(-90, 90);
            $longitude = $faker->longitude(-90, 90);
            $location = new Point($latitude, $longitude);
            $parking->setLocation($location);

            $now = new \DateTime();
            $parking->setCreatedAt($now);
            $parking->setUpdatedAt($now);

            $manager->persist($parking);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
