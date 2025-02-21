<?php

namespace App\DataFixtures;

use App\DataFixtures\CreditCardFixtures;
use Faker\Factory;
use App\Entity\Booking;
use App\Entity\Paiement;
use App\Entity\Traits\DataStatus;
use App\Repository\StatusRepository;
use App\Repository\BookingRepository;
use App\Repository\CreditCardRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class PaiementFixtures extends Fixture implements DependentFixtureInterface
{
    private StatusRepository $statusRepository;
    private CreditCardRepository $creditCardRepository;

    public function __construct(StatusRepository $statusRepository, CreditCardRepository $creditCardRepository)
    {
        $this->statusRepository = $statusRepository;
        $this->creditCardRepository = $creditCardRepository;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $statuses = $this->statusRepository->findAll();
        $creditCards = $this->creditCardRepository->findAll();

        for ($i = 0; $i < 10; $i++) {
            $randomStatus = $statuses[array_rand($statuses)];
            $randomCreditCard = ($i % 2 == 0) ? $creditCards[array_rand($creditCards)] : null;

            $paiement = new Paiement();
            $paiement->setStatus($randomStatus);
            $paiement->setTotalPrice($faker->randomFloat(2, 0, 100));
            $paiement->setCreditCard($randomCreditCard);
            if ($randomCreditCard) {
                $paiement->setCreditCardNumber($randomCreditCard->getNumber());
            } else {
                $paiement->setCreditCardNumber($faker->creditCardNumber);
            }

            $manager->persist($paiement);
        
            $this->addReference('paiement_' . $i, $paiement);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            StatusFixtures::class,
            CreditCardFixtures::class,
        ];
    }
}
