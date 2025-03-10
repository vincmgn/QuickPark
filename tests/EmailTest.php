<?php

namespace App\Tests\Entity;

use App\Entity\Email;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class EmailTest extends TestCase
{
    private function getEntity(): Email
    {
        return (new Email())
            ->setEmail("test@gmail.com");
    }

    public function testEmailisValid()
    {
        $email = $this->getEntity();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($email);
        $this->assertCount(0, $errors);

        $email->setEmail("test");
        $errors = $validator->validate($email);
        $this->assertCount(1, $errors);
        $this->assertEquals('The email ""test"" is not a valid email address.', $errors[0]->getMessage());
    }
}
