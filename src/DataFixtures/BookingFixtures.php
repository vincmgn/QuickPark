<?php

use Faker\Factory;
use App\Entity\Status;
use App\Entity\Booking;
use App\Entity\Parking;
use App\Repository\PriceRepository;
use App\DataFixtures\StatusFixtures;
use App\Repository\StatusRepository;
use App\DataFixtures\ParkingFixtures;
use App\Repository\ParkingRepository;
use App\Repository\PaiementRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class BookingFixtures extends Fixture implements DependentFixtureInterface
{
    private StatusRepository $statusRepository;
    private ParkingRepository $parkingRepository;
    private PriceRepository $priceRepository;
    private PaiementRepository $paiementRepository;

    public function __construct(StatusRepository $statusRepository, ParkingRepository $parkingRepository, PriceRepository $priceRepository, PaiementRepository $paiementRepository)
    {
        $this->statusRepository = $statusRepository;
        $this->parkingRepository = $parkingRepository;
        $this->priceRepository = $priceRepository;
        $this->paiementRepository = $paiementRepository;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $statuses = $this->statusRepository->findAll();
        $parkings = $this->parkingRepository->findAll();
        $prices = $this->priceRepository->findAll();
        $paiements = $this->paiementRepository->findAll();

        for ($i = 0; $i < 10; $i++) {
            $randomStatus = $statuses[array_rand($statuses)];
            $randomParking = $parkings[array_rand($parkings)];
            $randomPrice = $prices[array_rand($prices)];
            $randomPaiement = $paiements[array_rand($paiements)];

            $booking = new Booking();
            $status = ($randomPaiement->getStatus());
            if ($status == 'Confirmed') {
                $booking->setStatus($randomPaiement->getStatus());
            } else {
                $newStatus = new Status();
                $newStatus->setName('Pending');
                $booking->setStatus($newStatus);
            }
            $booking->setParking($randomParking);
            $booking->setPrice($randomPrice);
            $startDate = $faker->dateTimeBetween('-1 years', '+1 years');
            $endDate = (clone $startDate)->add($faker->randomElement($randomPrice->getDuration()));
            $booking->setStartDate($startDate);
            $booking->setEndDate($endDate);

            $now = new \DateTime();
            $booking->setCreatedAt($now);
            $booking->setUpdatedAt($now);

            $manager->persist($booking);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            StatusFixtures::class,
            ParkingFixtures::class,
            PriceFixtures::class,
            PaiementFixtures::class,
        ];
    }
}
