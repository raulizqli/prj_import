<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class StockRestrictionValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var StockRestriction $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if ( !is_numeric($value["stock"]) && !is_numeric($value["cost"]) )
        {
            $this->context->buildViolation('The stock and cost have to be integer following given: {{stock}} {{cost}}')
                ->setParameter('{{ stock }}', $value["stock"])
                ->setParameter('{{ cost }}', $value["cost"])
                ->addViolation();
        }
        else
        {
            if ( $value["cost"] < 5 && $value["stock"] < 10 )
            {

                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ stock }}', $value["stock"])
                    ->setParameter('{{ cost }}', $value["cost"])
                    ->addViolation();
            }
        }
    }
}
