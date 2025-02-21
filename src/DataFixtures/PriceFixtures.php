<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Price;
use App\Repository\ParkingRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class PriceFixtures extends Fixture implements DependentFixtureInterface
{
    private ParkingRepository $parkingRepository;

    public function __construct(ParkingRepository $parkingRepository)
    {
        $this->parkingRepository = $parkingRepository;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $parkings = $this->parkingRepository->findAll();

        for ($i = 0; $i < 100; $i++) {
            $randomParking = $parkings[array_rand($parkings)];
            $price = new Price();
            $days = random_int(1, 30);
            $dateInterval = new \DateInterval("P{$days}D");
            $price->setDuration($dateInterval);
            $price->setPrice($faker->randomFloat(2, 0, 100));
            $price->setCurrency($faker->currencyCode);
            $price->setParking($randomParking);

            $now = new \DateTime();
            $price->setCreatedAt($now);
            $price->setUpdatedAt($now);

            $manager->persist($price);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ParkingFixtures::class,
        ];
    }
}
