<?php

namespace App\Entity;

use App\Repository\ManagerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ManagerRepository::class)]
class Manager extends User
{
    #[ORM\OneToMany(mappedBy: 'manager', targetEntity: Property::class)]
    private Collection $properties;

    #[ORM\OneToMany(mappedBy: 'assignedManager', targetEntity: MaintenanceRequest::class)]
    private Collection $maintenanceRequests;

    public function __construct()
    {
        parent::__construct();
        $this->properties = new ArrayCollection();
        $this->maintenanceRequests = new ArrayCollection();
        $this->setRoles(['ROLE_MANAGER']);
    }

    /**
     * @return Collection<int, Property>
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    public function addProperty(Property $property): static
    {
        if (!$this->properties->contains($property)) {
            $this->properties->add($property);
            $property->setManager($this);
        }

        return $this;
    }

    public function removeProperty(Property $property): static
    {
        if ($this->properties->removeElement($property)) {
            // set the owning side to null (unless already changed)
            if ($property->getManager() === $this) {
                $property->setManager(null);
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

    public function addMaintenanceRequest(MaintenanceRequest $maintenanceRequest): static
    {
        if (!$this->maintenanceRequests->contains($maintenanceRequest)) {
            $this->maintenanceRequests->add($maintenanceRequest);
            $maintenanceRequest->setAssignedManager($this);
        }

        return $this;
    }

    public function removeMaintenanceRequest(MaintenanceRequest $maintenanceRequest): static
    {
        if ($this->maintenanceRequests->removeElement($maintenanceRequest)) {
            // set the owning side to null (unless already changed)
            if ($maintenanceRequest->getAssignedManager() === $this) {
                $maintenanceRequest->setAssignedManager(null);
            }
        }

        return $this;
    }
}