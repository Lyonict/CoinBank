<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Validator\ValidSponsorCode;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\Email()]
    #[Assert\NotBlank()]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[Assert\NotCompromisedPassword()]
    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank()]
    #[ORM\Column(length: 30, unique: true)]
    private ?string $username = null;

    // Even though the user can only deposit a max of 100000, they are allowed to have more on their account after a sell
    // So we don't limit the bank here
    #[Assert\PositiveOrZero()]
    #[ORM\Column(nullable: true)]
    private ?float $bank = null;

    #[ORM\Column(length: 5)]
    #[Assert\Language()]
    private ?string $preferedLocale = null;

    #[ValidSponsorCode]
    #[ORM\Column(name: "sponsor_code", type: Types::GUID)]
    private ?string $sponsorCode = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'sponsoredUsers')]
    private ?self $sponsor = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'sponsor')]
    private Collection $sponsoredUsers;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $transactions;

    #[Assert\NotNull()]
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $isFrozen = false;

    public function __construct()
    {
        $this->sponsoredUsers = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->isFrozen = false;
    }

    public function __toString(): string
    {
        return $this->email ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Add ROLE_USER only if the user doesn't have ROLE_ADMIN
        if (!in_array('ROLE_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getBank(): ?float
    {
        return $this->bank;
    }

    public function setBank(?float $bank): static
    {
        $this->bank = $bank;

        return $this;
    }

    public function getPreferedLocale(): ?string
    {
        return $this->preferedLocale;
    }

    public function setPreferedLocale(string $preferedLocale): static
    {
        $this->preferedLocale = $preferedLocale;

        return $this;
    }

    public function getSponsorCode(): ?string
    {
        return $this->sponsorCode;
    }

    public function setSponsorCode(string $sponsorCode): static
    {
        $this->sponsorCode = $sponsorCode;

        return $this;
    }

    public function getSponsor(): ?self
    {
        return $this->sponsor;
    }

    public function setSponsor(?self $sponsor): static
    {
        $this->sponsor = $sponsor;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSponsoredUsers(): Collection
    {
        return $this->sponsoredUsers;
    }

    public function addSponsoree(self $user): static
    {
        if (!$this->sponsoredUsers->contains($user)) {
            $this->sponsoredUsers->add($user);
            $user->setSponsor($this);
        }

        return $this;
    }

    public function removeSponsoree(self $user): static
    {
        if ($this->sponsoredUsers->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getSponsor() === $this) {
                $user->setSponsor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setUser($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getUser() === $this) {
                $transaction->setUser(null);
            }
        }

        return $this;
    }

    public function getIsFrozen(): ?bool
    {
        return $this->isFrozen;
    }

    public function setIsFrozen(bool $isFrozen): static
    {
        $this->isFrozen = $isFrozen;

        return $this;
    }
}
