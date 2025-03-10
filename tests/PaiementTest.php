<?php

namespace App\Tests\Entity;

use App\Entity\Paiement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class PaiementTest extends TestCase
{
    private function getEntity(): Paiement
    {
        return (new Paiement())
            ->setCreditCardNumber("1234567890123456")
            ->setTotalPrice(10);
    }

    public function testCreditCardNumberisValid()
    {
        $paiement = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($paiement);
        $this->assertCount(0, $errors);

        $paiement->setCreditCardNumber("123456789012345");
        $errors = $validator->validate($paiement);
        $this->assertCount(1, $errors);
        $this->assertEquals("The credit card number must be exactly 16 characters long", $errors[0]->getMessage());

        $paiement->setCreditCardNumber("123456789012345B");
        $errors = $validator->validate($paiement);
        $this->assertCount(1, $errors);
        $this->assertEquals("The credit card number must contain only digits", $errors[0]->getMessage());
    }

    public function testTotalPriceisValid()
    {
        $paiement = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($paiement);

        $this->assertCount(0, $errors);
        $paiement->setTotalPrice(-10);
        $errors = $validator->validate($paiement);
        $this->assertCount(1, $errors);
        $this->assertEquals("The total price must be greater than 0.", $errors[0]->getMessage());
    }
}
