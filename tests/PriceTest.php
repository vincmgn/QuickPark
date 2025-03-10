<?php

namespace App\Tests\Entity;

use App\Entity\Price;
use App\Entity\Parking;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;

class PriceTest extends TestCase
{
    private function getEntity(): Price
    {
        $parking = (new Parking())->setName("Test")->setDescription("Ceci est un test")->setLocation(new Point(-45, 45))->setIsEnabled(True)->setCreatedAt(new \DateTime('2025-01-01'))->setUpdatedAt(new \DateTime('2025-01-01'));
        return (new Price())
            ->setPrice(10)
            ->setDuration(new \DateInterval('PT1H'))
            ->setParking($parking);
    }

    public function testPriceisValid()
    {
        $price = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($price);

        $this->assertCount(0, $errors);
        $price->setPrice(-10);
        $errors = $validator->validate($price);
        $this->assertCount(1, $errors);
        $this->assertEquals("The price must be greater than 0.", $errors[0]->getMessage());
    }

    public function testDurationisValid()
    {
        $price = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($price);

        $this->assertCount(0, $errors);
        $price->setDuration(new \DateInterval('PT0H'));
        $errors = $validator->validate($price);
        $this->assertCount(1, $errors);
        $this->assertEquals("The duration must be greater than 0 minute.", $errors[0]->getMessage());
    }
}
