<?php

namespace App\Tests\Entity;

use App\Entity\Phone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Phone::class)]
class PhoneTest extends TestCase
{
    private function getEntity(): Phone
    {
        return (new Phone())
            ->setNumber("0606060606");
    }

    public function testNumberisValid()
    {
        $phone = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($phone);
        $this->assertCount(0, $errors);

        $phone->setNumber("060606060");
        $errors = $validator->validate($phone);
        $this->assertCount(2, $errors);
        $this->assertEquals("The phone number must be at least 10 characters long", $errors[0]->getMessage());
        $this->assertEquals("The phone number is not valid", $errors[1]->getMessage());

        $phone->setNumber("060606060A");
        $errors = $validator->validate($phone);
        $this->assertCount(1, $errors);
        $this->assertEquals("The phone number is not valid", $errors[0]->getMessage());
    }
}
