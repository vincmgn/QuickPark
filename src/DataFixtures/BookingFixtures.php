<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Booking;
use App\Entity\Traits\DataStatus;
use App\Repository\PriceRepository;
use App\Repository\StatusRepository;
use App\Repository\ParkingRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class BookingFixtures extends Fixture implements DependentFixtureInterface
{
    private StatusRepository $statusRepository;
    private ParkingRepository $parkingRepository;
    private PriceRepository $priceRepository;

    public function __construct(StatusRepository $statusRepository, ParkingRepository $parkingRepository, PriceRepository $priceRepository)
    {
        $this->statusRepository = $statusRepository;
        $this->parkingRepository = $parkingRepository;
        $this->priceRepository = $priceRepository;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $statuses = $this->statusRepository->findAll();
        $parkings = $this->parkingRepository->findAll();
        $prices = $this->priceRepository->findAll();

        for ($i = 0; $i < 10; $i++) {
            $randomStatus = $statuses[array_rand($statuses)];
            $randomParking = $parkings[array_rand($parkings)];
            $randomPrice = $prices[array_rand($prices)];

            $booking = new Booking();
            $booking->setParking($randomParking);
            $booking->setPrice($randomPrice);
            $startDate = $faker->dateTimeBetween('-1 years', '+1 years');
            $endDate = $faker->dateTimeBetween($startDate, '+1 years');
            $booking->setStartDate($startDate);
            $booking->setEndDate($endDate);
            $booking->setStatus($randomStatus);

            $now = new \DateTime();
            $booking->setCreatedAt($now);
            $booking->setUpdatedAt($now);

            $manager->persist($booking);

            $this->addReference('booking_' . $i, $booking);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PriceFixtures::class,
            StatusFixtures::class,
            ParkingFixtures::class,
        ];
    }
}
