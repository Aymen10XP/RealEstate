<?php

namespace App\Entity;

use App\Repository\PropertyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropertyRepository::class)]
class Property
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    private ?string $state = null;

    #[ORM\Column(length: 20)]
    private ?string $zipCode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $rentAmount = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'available'; // available, occupied, maintenance

    #[ORM\ManyToOne(inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\OneToMany(mappedBy: 'property', targetEntity: Lease::class)]
    private Collection $leases;

    #[ORM\OneToMany(mappedBy: 'property', targetEntity: MaintenanceRequest::class)]
    private Collection $maintenanceRequests;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $bedrooms = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $bathrooms = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $squareFootage = null;

    public function __construct()
    {
        $this->leases = new ArrayCollection();
        $this->maintenanceRequests = new ArrayCollection();
    }

    // ... (getters and setters for basic fields)

    public function getLeases(): Collection
    {
        return $this->leases;
    }

    public function addLease(Lease $lease): static
    {
        if (!$this->leases->contains($lease)) {
            $this->leases->add($lease);
            $lease->setProperty($this);
        }

        return $this;
    }

    public function removeLease(Lease $lease): static
    {
        if ($this->leases->removeElement($lease)) {
            // set the owning side to null (unless already changed)
            if ($lease->getProperty() === $this) {
                $lease->setProperty(null);
            }
        }

        return $this;
    }

    public function getMaintenanceRequests(): Collection
    {
        return $this->maintenanceRequests;
    }

    public function addMaintenanceRequest(MaintenanceRequest $maintenanceRequest): static
    {
        if (!$this->maintenanceRequests->contains($maintenanceRequest)) {
            $this->maintenanceRequests->add($maintenanceRequest);
            $maintenanceRequest->setProperty($this);
        }

        return $this;
    }

    public function removeMaintenanceRequest(MaintenanceRequest $maintenanceRequest): static
    {
        if ($this->maintenanceRequests->removeElement($maintenanceRequest)) {
            // set the owning side to null (unless already changed)
            if ($maintenanceRequest->getProperty() === $this) {
                $maintenanceRequest->setProperty(null);
            }
        }

        return $this;
    }

    // Helper methods
    public function getFullAddress(): string
    {
        return $this->address . ', ' . $this->city . ', ' . $this->state . ' ' . $this->zipCode;
    }

    public function hasActiveLease(): bool
    {
        foreach ($this->leases as $lease) {
            if ($lease->isActive()) {
                return true;
            }
        }
        return false;
    }

    public function getActiveLease(): ?Lease
    {
        foreach ($this->leases as $lease) {
            if ($lease->isActive()) {
                return $lease;
            }
        }
        return null;
    }

    public function getPendingMaintenanceRequests(): Collection
    {
        return $this->maintenanceRequests->filter(function(MaintenanceRequest $request) {
            return in_array($request->getStatus(), ['submitted', 'in_progress']);
        });
    }

    public function __toString(): string
    {
        return $this->getFullAddress();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getRentAmount(): ?string
    {
        return $this->rentAmount;
    }

    public function setRentAmount(string $rentAmount): static
    {
        $this->rentAmount = $rentAmount;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getBedrooms(): ?int
    {
        return $this->bedrooms;
    }

    public function setBedrooms(?int $bedrooms): static
    {
        $this->bedrooms = $bedrooms;

        return $this;
    }

    public function getBathrooms(): ?int
    {
        return $this->bathrooms;
    }

    public function setBathrooms(?int $bathrooms): static
    {
        $this->bathrooms = $bathrooms;

        return $this;
    }

    public function getSquareFootage(): ?string
    {
        return $this->squareFootage;
    }

    public function setSquareFootage(?string $squareFootage): static
    {
        $this->squareFootage = $squareFootage;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}