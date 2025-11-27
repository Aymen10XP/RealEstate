<?php

namespace App\Entity;

use App\Repository\TenantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
class Tenant extends User
{
    #[ORM\OneToMany(mappedBy: 'tenant', targetEntity: Lease::class)]
    private Collection $leases;

    #[ORM\OneToMany(mappedBy: 'tenant', targetEntity: RentPayment::class)]
    private Collection $rentPayments;

    #[ORM\OneToMany(mappedBy: 'tenant', targetEntity: MaintenanceRequest::class)]
    private Collection $maintenanceRequests;

    public function __construct()
    {
        parent::__construct();
        $this->leases = new ArrayCollection();
        $this->rentPayments = new ArrayCollection();
        $this->maintenanceRequests = new ArrayCollection();
        $this->setRoles(['ROLE_TENANT']);
    }

    /**
     * @return Collection<int, Lease>
     */
    public function getLeases(): Collection
    {
        return $this->leases;
    }

    public function addLease(Lease $lease): static
    {
        if (!$this->leases->contains($lease)) {
            $this->leases->add($lease);
            $lease->setTenant($this);
        }

        return $this;
    }

    public function removeLease(Lease $lease): static
    {
        if ($this->leases->removeElement($lease)) {
            // set the owning side to null (unless already changed)
            if ($lease->getTenant() === $this) {
                $lease->setTenant(null);
            }
        }

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
            $rentPayment->setTenant($this);
        }

        return $this;
    }

    public function removeRentPayment(RentPayment $rentPayment): static
    {
        if ($this->rentPayments->removeElement($rentPayment)) {
            // set the owning side to null (unless already changed)
            if ($rentPayment->getTenant() === $this) {
                $rentPayment->setTenant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MaintenanceRequest>
     */
    public function getMaintenanceRequests(): Collection
    {
        return $this->maintenanceRequests;
    }


    public function getActiveLease(): ?Lease
    {
        $currentDate = new \DateTime();

        foreach ($this->leases as $lease) {
            if ($lease->getStatus() === 'active' &&
                $lease->getStartDate() <= $currentDate &&
                $lease->getEndDate() >= $currentDate) {
                return $lease;
            }
        }

        return null;
    }

    public function getCurrentProperty(): ?Property
    {
        $activeLease = $this->getActiveLease();
        return $activeLease ? $activeLease->getProperty() : null;
    }

    public function addMaintenanceRequest(MaintenanceRequest $maintenanceRequest): static
    {
        if (!$this->maintenanceRequests->contains($maintenanceRequest)) {
            $this->maintenanceRequests->add($maintenanceRequest);
            $maintenanceRequest->setTenant($this);
        }

        return $this;
    }

    public function removeMaintenanceRequest(MaintenanceRequest $maintenanceRequest): static
    {
        if ($this->maintenanceRequests->removeElement($maintenanceRequest)) {
            // set the owning side to null (unless already changed)
            if ($maintenanceRequest->getTenant() === $this) {
                $maintenanceRequest->setTenant(null);
            }
        }

        return $this;
    }
}