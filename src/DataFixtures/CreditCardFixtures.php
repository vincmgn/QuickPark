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
            $creditCard->setExpirationDate($faker->dateTimeBetween('-1 years', '+1 years'));

            $now = new \DateTime();
            $creditCard->setCreatedAt($now);
            $creditCard->setUpdatedAt($now);

            $manager->persist($creditCard);
        }
        $manager->flush();
    }
}