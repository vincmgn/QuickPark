<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\CreditCard;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class CreditCardFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($i = 0; $i < 10; $i++) {
            $creditCard = new CreditCard();
            $creditCard->setNumber($faker->creditCardNumber);
            $expirationDate = $faker->dateTimeBetween('-1 years', '+1 years');
            $expirationDate->setDate($expirationDate->format('Y'), $expirationDate->format('m'), $expirationDate->format('t'));
            $expirationDate->setTime(0, 0);
            $creditCard->setExpirationDate($expirationDate);

            $now = new \DateTime();
            $creditCard->setCreatedAt($now);
            $creditCard->setUpdatedAt($now);

            $manager->persist($creditCard);
        }
        $manager->flush();
    }
}
