<?php

use App\DataFixtures\CreditCardFixtures;
use Faker\Factory;
use App\Entity\Booking;
use App\Entity\Paiement;
use App\DataFixtures\StatusFixtures;
use App\Repository\StatusRepository;
use App\Repository\BookingRepository;
use App\Repository\CreditCardRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class PaiementFixtures extends Fixture implements DependentFixtureInterface
{
    private StatusRepository $statusRepository;
    private BookingRepository $bookingRepository;
    private CreditCardRepository $creditCardRepository;

    public function __construct(StatusRepository $statusRepository, BookingRepository $bookingRepository, CreditCardRepository $creditCardRepository)
    {
        $this->statusRepository = $statusRepository;
        $this->bookingRepository = $bookingRepository;
        $this->creditCardRepository = $creditCardRepository;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $statuses = $this->statusRepository->findAll();
        $bookings = $this->bookingRepository->findAll();
        $creditCards = $this->creditCardRepository->findAll();

        for ($i = 0; $i < 10; $i++) {
            $randomStatus = $statuses[array_rand($statuses)];
            $randomBooking = $bookings[array_rand($bookings)];
            $randomCreditCard = ($i % 2 == 0) ? $creditCards[array_rand($creditCards)] : null;

            $paiement = new Paiement();
            $paiement->setStatus($randomStatus);
            $paiement->setBooking($randomBooking);
            $paiement->setTotalPrice($faker->randomFloat(2, 0, 100));
            $paiement->setCreditCard($randomCreditCard);
            if($randomCreditCard) {
                $paiement->setCreditCardNumber($randomCreditCard->getNumber());
            } else {
                $paiement->setCreditCardNumber($faker->creditCardNumber);
            }

            $paiement->setCreatedAt($faker->dateTimeBetween('-1 years', '+1 years'));
            $paiement->setUpdatedAt($faker->dateTimeBetween('-1 years', '+1 years'));

            $manager->persist($paiement);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            StatusFixtures::class,
            BookingFixtures::class,
            CreditCardFixtures::class,
        ];
    }
}
