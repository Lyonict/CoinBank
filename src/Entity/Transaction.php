<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Enum\TransactionType;
use Doctrine\DBAL\Types\Types;

#[ORM\Table(name: '`transaction`')]
#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cryptocurrency $cryptocurrency = null;

    #[Assert\Positive()]
    #[ORM\Column]
    private ?float $cryptoAmount = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 4, enumType: TransactionType::class)]
    private ?TransactionType $transactionType = null;

    #[ORM\Column]
    private ?float $dollarAmount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getCryptocurrency(): ?Cryptocurrency
    {
        return $this->cryptocurrency;
    }

    public function setCryptocurrency(?Cryptocurrency $cryptocurrency): static
    {
        $this->cryptocurrency = $cryptocurrency;

        return $this;
    }

    public function getCryptoAmount(): ?float
    {
        return $this->cryptoAmount;
    }

    public function setCryptoAmount(float $cryptoAmount): static
    {
        $this->cryptoAmount = $cryptoAmount;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getTransactionType(): ?TransactionType
    {
        return $this->transactionType;
    }

    public function setTransactionType(TransactionType $transactionType): static
    {
        $this->transactionType = $transactionType;

        return $this;
    }

    public function getDollarAmount(): ?float
    {
        return $this->dollarAmount;
    }

    public function setDollarAmount(float $dollarAmount): static
    {
        $this->dollarAmount = $dollarAmount;

        return $this;
    }
}
