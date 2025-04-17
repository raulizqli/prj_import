<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class CSVHeaderStructureValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var CSVHeaderStructure $constraint */

        if (null === $value || '' === $value)
        {
            $this->context->buildViolation("Value can't be Null")
                ->addViolation();
            return;
        }

        if ( !is_array($value) )
        {
            $this->context->buildViolation("Value have to be array")
                    ->addViolation();
            return;
        }

        if ( !isset($value["headers"]) && !isset($value["values"]) )
        {
            $this->context->buildViolation("headers and values are required as parameters")
                    ->addViolation();
            return;
        }

        // TODO: implement the validation here
        $headerCount = count($value['headers']);
        if ( $headerCount != count($value['values']) || $value['headers'] != $value["values"] )
        {            
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ columns }}', $headerCount)
                ->setParameter('{{ column_names }}', implode(', ', $value['headers']) )
                ->addViolation();
        }
    }
}
