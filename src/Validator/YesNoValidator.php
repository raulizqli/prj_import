<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class YesNoValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var YesNo $constraint */

        if (null === $value)
        {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', 'null')
                ->addViolation();
            return;
        }

        if (!in_array(strtolower($value), ['yes', ''], true))
        {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->addViolation();
        }
    }
}
