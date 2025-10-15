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

    public function __construct()
    {
        $this->leases = new ArrayCollection();
        $this->maintenanceRequests = new ArrayCollection();
    }

    // Getters  & setters
}