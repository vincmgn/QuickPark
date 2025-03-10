<?php
namespace App\Tests\Entity;

use App\Entity\Parking;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use Lcobucci\JWT\Validation\Validator;
use Symfony\Component\Validator\Validation;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\Bestiary;
use App\Entity\Creature;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ParkingTest extends WebTestCase
{
    private function getEntity() : Parking
    {
        return (new Parking())->setName("Test")->setDescription("Ceci est un test")->setLocation(new Point(-92, 45))->setIsEnabled(True)->setCreatedAt(new \DateTime('2025-01-01'))->setUpdatedAt(new \DateTime('2025-01-01'));
    }

    public function testNameisValid(){
        $parking = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($parking);

        $this->assertCount(0, $errors);
        $parking->setName("T");
        $errors = $validator->validate($parking);
        $this->assertCount(1, $errors);

        $this->assertEquals("This value should not be blank.", $errors[0]->getMessage());
        $this->assertEquals("Your Chimpoko name must be at least 3 characters long", $errors[1]->getMessage());
    }
}