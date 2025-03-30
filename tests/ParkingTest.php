<?php
namespace App\Tests\Entity;

use App\Entity\Parking;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;
use Symfony\Component\Validator\Validation;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Parking::class)]
#[UsesClass(Point::class)]
class ParkingTest extends TestCase
{
    private function getEntity() : Parking
    {
        return (new Parking())->setName("Test")->setDescription("Ceci est un test")->setLocation(new Point(-45, 45))->setIsEnabled(True)->setCreatedAt(new \DateTime('2025-01-01'))->setUpdatedAt(new \DateTime('2025-01-01'));
    }

    public function testNameisValid(){
        $parking = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($parking);

        $this->assertCount(0, $errors);
        $parking->setName("");
        $errors = $validator->validate($parking);
        $this->assertCount(2, $errors);

        $this->assertEquals("This value should not be blank.", $errors[0]->getMessage());
        $this->assertEquals("The parking name must be at least 3 characters long", $errors[1]->getMessage());
    }

    public function testDescriptionisValid(){
        $parking = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($parking);

        $this->assertCount(0, $errors);
        $parking->setDescription("");
        $errors = $validator->validate($parking);
        $this->assertCount(2, $errors);
        $this->assertEquals("This value should not be blank.", $errors[0]->getMessage());
        $this->assertEquals("The parking description must be at least 10 characters long", $errors[1]->getMessage());

        $parking->setDescription("T");
        $errors = $validator->validate($parking);
        $this->assertCount(1, $errors);
        $this->assertEquals("The parking description must be at least 10 characters long", $errors[0]->getMessage());
    }

    public function testLocationisValid(){
        $parking = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($parking);

        $this->assertCount(0, $errors);
        $parking->setLocation(new Point(0, 0));
        $errors = $validator->validate($parking);
        $this->assertCount(0, $errors);

        try {
            $parking->setLocation(new Point(-192, 45));
            $errors = $validator->validate($parking);
        } catch (\LongitudeOne\Spatial\Exception\InvalidValueException $e) {
            $errors = [$e->getMessage()];
            $this->assertCount(1, $errors);
            $this->assertEquals("Out of range longitude value, longitude must be between -180 and 180, got \"-192\".", $errors[0]);
        }

        try {
            $parking->setLocation(new Point(0, 92));
            $errors = $validator->validate($parking);
        } catch (\LongitudeOne\Spatial\Exception\InvalidValueException $e) {
            $errors = [$e->getMessage()];
            $this->assertCount(1, $errors);
            $this->assertEquals("Out of range latitude value, latitude must be between -90 and 90, got \"92\".", $errors[0]);
        }
    }
}