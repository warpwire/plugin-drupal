<?php

namespace Symfony\Component\Validator\Tests\Constraints;

use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

use Drupal\warpwire\Plugin\Validation\Constraint\WarpwireValidSourceConstraint;
use Drupal\warpwire\Plugin\Validation\Constraint\WarpwireValidSourceConstraintValidator;

class WarpwireValidSourceConstraintValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): WarpwireValidSourceConstraintValidator
    {
        return new WarpwireValidSourceConstraintValidator();
    }

    public function testValidUrlWithoutQuery()
    {
        $input = new class
        {
            public $value = "https://1996-secure-link-ui.testing-public.warpwire.net/w/CwAAAA/";
        };
        $this->validator->validate([$input], new WarpwireValidSourceConstraint());
        $this->assertNoViolation();
    }

    public function testInvalidUrl()
    {
        $input = new class
        {
            public $value = "abc123";
        };
        $this->validator->validate([$input], new WarpwireValidSourceConstraint());
        $this->buildViolation('The given link does not contain a Warpwire asset shortcode.')
            ->setParameter('%value', $input->value)
            ->assertRaised();
    }

    public function testInvalidUrlNoShortcode()
    {
        $input = new class
        {
            public $value = "https://abc.warpwire.net";
        };
        $this->validator->validate([$input], new WarpwireValidSourceConstraint());
        $this->buildViolation('The given link does not contain a Warpwire asset shortcode.')
            ->setParameter('%value', $input->value)
            ->assertRaised();
    }
}
