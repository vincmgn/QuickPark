<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\CreditCard;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class CreditCardFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        for ($i = 0; $i < 10; $i++) {
            $user = $this->getReference('user_' . $i, User::class);            
            $creditCard = new CreditCard();
            $creditCard->setNumber($faker->creditCardNumber);
            $expirationDate = $faker->dateTimeBetween('-1 years', '+1 years');
            $expirationDate->setDate($expirationDate->format('Y'), $expirationDate->format('m'), $expirationDate->format('t'));
            $expirationDate->setTime(0, 0);
            $creditCard->setExpirationDate($expirationDate);
            $creditCard->setOwner($user);
            $creditCard->setOwnerName($faker->name);

            $now = new \DateTime();
            $creditCard->setCreatedAt($now);
            $creditCard->setUpdatedAt($now);

            $manager->persist($creditCard);
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
