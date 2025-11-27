<?php

namespace App\Entity;

use App\Repository\LeaseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeaseRepository::class)]
class Lease
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $monthlyRent = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $securityDeposit = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'active'; // active, expired, terminated

    #[ORM\ManyToOne(inversedBy: 'leases')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Property $property = null;

    #[ORM\ManyToOne(inversedBy: 'leases')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tenant $tenant = null;

    #[ORM\OneToMany(mappedBy: 'lease', targetEntity: RentPayment::class)]
    private Collection $rentPayments;

    public function __construct()
    {
        $this->rentPayments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getMonthlyRent(): ?string
    {
        return $this->monthlyRent;
    }

    public function setMonthlyRent(string $monthlyRent): static
    {
        $this->monthlyRent = $monthlyRent;

        return $this;
    }

    public function getSecurityDeposit(): ?string
    {
        return $this->securityDeposit;
    }

    public function setSecurityDeposit(string $securityDeposit): static
    {
        $this->securityDeposit = $securityDeposit;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setProperty(?Property $property): static
    {
        $this->property = $property;

        return $this;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function setTenant(?Tenant $tenant): static
    {
        $this->tenant = $tenant;

        return $this;
    }

    /**
     * @return Collection<int, RentPayment>
     */
    public function getRentPayments(): Collection
    {
        return $this->rentPayments;
    }

    public function addRentPayment(RentPayment $rentPayment): static
    {
        if (!$this->rentPayments->contains($rentPayment)) {
            $this->rentPayments->add($rentPayment);
            $rentPayment->setLease($this);
        }

        return $this;
    }

    public function removeRentPayment(RentPayment $rentPayment): static
    {
        if ($this->rentPayments->removeElement($rentPayment)) {
            // set the owning side to null (unless already changed)
            if ($rentPayment->getLease() === $this) {
                $rentPayment->setLease(null);
            }
        }

        return $this;
    }

    public function isActive(): bool
    {
        $now = new \DateTime();
        return $this->status === 'active' &&
            $this->startDate <= $now &&
            $this->endDate >= $now;
    }
}