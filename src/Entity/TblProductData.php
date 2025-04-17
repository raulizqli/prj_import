<?php

namespace App\Entity;

use App\Repository\TblProductDataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TblProductDataRepository::class)]
class TblProductData
{
    const CSV_NAME = "Product Name";
    const CSV_CODE = "Product Code";
    const CSV_DESC = "Product Description";
    const CSV_STOCK = "Stock";
    const CSV_COST_GBP = "Cost in GBP";
    const CSV_DISCONTINUED = "Discontinued";

    const CSV_HEADER = [
        self::CSV_CODE,
        self::CSV_NAME,
        self::CSV_DESC,
        self::CSV_STOCK,
        self::CSV_COST_GBP,
        self::CSV_DISCONTINUED
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(
        min: 1, 
        max: 50, 
        minMessage: 'The '.self::CSV_NAME.' {{ value }} is not between 1-50 characters.',
        maxMessage: 'The '.self::CSV_NAME.' {{ value }} is not between 1-50 characters.'
    )]
    #[ORM\Column(length: 50)]
    private ?string $strProductName = null;

    #[Assert\NotBlank]
    #[Assert\Length(
        min: 1, 
        max: 255,
        minMessage: 'The '.self::CSV_DESC.' {{ value }} is not between 1-255 characters.',
        maxMessage: 'The '.self::CSV_DESC.' {{ value }} is not between 1-255 characters.'
    )]
    #[ORM\Column(length: 255)]
    private ?string $strProductDesc = null;

    #[Assert\NotBlank]
    #[Assert\Length(
        min: 1,
        max: 10,
        minMessage: 'The '.self::CSV_CODE.' {{ value }} is not between 1-10 characters.',
        maxMessage: 'The '.self::CSV_CODE.' {{ value }} is not between 1-10 characters.'
    )]
    #[ORM\Column(length: 10)]
    private ?string $strProductCode = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dtmAdded = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dtmDiscontinued = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $stmTimestamp = null;

    #[ORM\Column]
    private ?int $intStockLevel = null;

    #[Assert\Type(type: 'float', message: 'The value {{ value }} is not a valid float.')]
    #[Assert\LessThan(
        value: 1000,
        message: 'The cost must be less than 1000.'
    )]
    #[ORM\Column]
    private ?float $dblCostInGBP = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStrProductName(): ?string
    {
        return $this->strProductName;
    }

    public function setStrProductName(string $strProductName): static
    {
        $this->strProductName = $strProductName;
        return $this;
    }

    public function getStrProductDesc(): ?string
    {
        return $this->strProductDesc;
    }

    public function setStrProductDesc(string $strProductDesc): static
    {
        $this->strProductDesc = $strProductDesc;

        return $this;
    }

    public function getStrProductCode(): ?string
    {
        return $this->strProductCode;
    }

    public function setStrProductCode(string $strProductCode): static
    {
        $this->strProductCode = $strProductCode;
        return $this;
    }

    public function getDtmAdded(): ?\DateTimeInterface
    {
        return $this->dtmAdded;
    }

    public function setDtmAdded(?\DateTimeInterface $dtmAdded): static
    {
        $this->dtmAdded = $dtmAdded;

        return $this;
    }

    public function getDtmDiscontinued(): ?\DateTimeInterface
    {
        return $this->dtmDiscontinued;
    }

    public function setDtmDiscontinued(?\DateTimeInterface $dtmDiscontinued): static
    {
        $this->dtmDiscontinued = $dtmDiscontinued;

        return $this;
    }

    public function getStmTimestamp(): ?\DateTimeInterface
    {
        return $this->stmTimestamp;
    }

    public function setStmTimestamp(\DateTimeInterface $stmTimestamp): static
    {
        $this->stmTimestamp = $stmTimestamp;

        return $this;
    }

    public function getIntStockLevel(): ?int
    {
        return $this->intStockLevel;
    }

    public function setIntStockLevel(?int $intStockLevel): self
    {
        $this->intStockLevel = $intStockLevel;

        return $this;
    }

    public function getDblCostInGBP(): ?float
    {
        return $this->dblCostInGBP;
    }

    public function setDblCostInGBP(?float $dblCostInGBP): self
    {
        $this->dblCostInGBP = $dblCostInGBP;

        return $this;
    }
    
    public function setDiscontinued(string $discontinued): self
    {
        if ( strtolower($discontinued) == 'yes' )
        {
            $this->dtmDiscontinued = new \DateTime();
        }
        return $this;
    }

    public function init(array $row):self
    {
        $this->setStrProductName($row[self::CSV_NAME]);
        $this->setStrProductCode($row[self::CSV_CODE]);
        $this->setStrProductDesc($row[self::CSV_DESC]);
        $this->setIntStockLevel((int)$row[self::CSV_STOCK]);
        $this->setDblCostInGBP((float)$row[self::CSV_COST_GBP]);
        $this->setDiscontinued($row[self::CSV_DISCONTINUED]);
        $this->setDtmAdded(new \DateTime());
        $this->setStmTimestamp(new \DateTime());
        return $this;
    }
}
