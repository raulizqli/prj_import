<?php

namespace App\Entity;
use Symfony\Component\Validator\ConstraintViolationList;

class RowErrors
{
    private ?string $strRow = null;

    private array $arrErrors = [];

    public function getStrRow(): ?string
    {
        return $this->strRow;
    }

    public function setStrRow(string $strRow): static
    {
        $this->strRow = $strRow;

        return $this;
    }

    public function getArrErrors(): array
    {
        return $this->arrErrors;
    }

    public function setArrErrors(?ConstraintViolationList $arrErrors): static
    {
        foreach( $arrErrors as $error )
        {
            $this->arrErrors[] = $error->getMessage();    
        }
        return $this;
    }

    public function getErrorMessages(): string
    {
        return implode(', ', $this->arrErrors);
    }

    public function hasErrors(): bool
    {
        return count( $this->arrErrors ) > 0; 
    }
}
