<?php

namespace App\Tests\Entity;

use App\Entity\CreditCard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreditCard::class)]
class CreditCardTest extends TestCase
{
    private function getEntity(): CreditCard
    {
        return (new CreditCard())
            ->setNumber("1234567890123456")
            ->setExpirationDate((new \DateTime())->modify('+1 year'));
    }

    public function testNumberisValid()
    {
        $creditCard = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($creditCard);
        $this->assertCount(0, $errors);

        $creditCard->setNumber("123456789012345");
        $errors = $validator->validate($creditCard);
        $this->assertCount(1, $errors);
        $this->assertEquals("The credit card number must be exactly 16 characters long", $errors[0]->getMessage());

        $creditCard->setNumber("123456789012345B");
        $errors = $validator->validate($creditCard);
        $this->assertCount(1, $errors);
        $this->assertEquals("The credit card number must contain only digits", $errors[0]->getMessage());
    }

    public function testExpirationDateisValid()
    {
        $creditCard = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($creditCard);

        $this->assertCount(0, $errors);
        $creditCard->setExpirationDate(new \DateTime('2024-01-01'));
        $errors = $validator->validate($creditCard);
        $this->assertCount(1, $errors);
        $this->assertEquals("The expiration date must be in the future", $errors[0]->getMessage());
    }
}
