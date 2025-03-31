<?php

namespace App\Tests\Entity;

use App\Entity\Booking;
use App\Entity\Parking;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;

#[CoversClass(Booking::class)]
#[UsesClass(Parking::class)]
class BookingTest extends TestCase
{
    private function getEntity(): Booking
    {
        $parking = (new Parking())->setName("Test")->setDescription("Ceci est un test")->setLocation(new Point(-45, 45))->setIsEnabled(True)->setCreatedAt(new \DateTime('2025-01-01'))->setUpdatedAt(new \DateTime('2025-01-01'));
        return (new Booking())->setParking($parking)->setPrice($parking->getPrices()[0])->setStartDate(new \DateTime('2025-01-01'))->setEndDate(new \DateTime('2025-01-02'));
    }

    public function testStartDateisValid()
    {
        $booking = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($booking);

        $this->assertCount(0, $errors);
        $booking->setStartDate(new \DateTime('2025-01-02'));
        $errors = $validator->validate($booking);
        $this->assertCount(1, $errors);
        $this->assertEquals("The start date must be before the end date", $errors[0]->getMessage());
    }

    public function testEndDateisValid()
    {
        $booking = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($booking);

        $this->assertCount(0, $errors);
        $booking->setEndDate(new \DateTime('2025-01-01'));
        $errors = $validator->validate($booking);
        $this->assertCount(1, $errors);
        $this->assertEquals("The start date must be before the end date", $errors[0]->getMessage());
    }
}
