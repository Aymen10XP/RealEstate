<?php

namespace App\DataFixtures;

use App\Entity\Manager;
use App\Entity\Owner;
use App\Entity\Tenant;
use App\Entity\Property;
use App\Entity\Lease;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create Manager
        $managerUser = new Manager();
        $managerUser->setEmail('manager@example.com');
        $managerUser->setFirstName('John');
        $managerUser->setLastName('Manager');
        $managerUser->setPhone('555-0101');
        $managerUser->setPassword($this->passwordHasher->hashPassword($managerUser, 'password'));
        $manager->persist($managerUser);

        // Create Owner
        $ownerUser = new Owner();
        $ownerUser->setEmail('owner@example.com');
        $ownerUser->setFirstName('Sarah');
        $ownerUser->setLastName('Owner');
        $ownerUser->setPhone('555-0102');
        $ownerUser->setPassword($this->passwordHasher->hashPassword($ownerUser, 'password'));
        $manager->persist($ownerUser);

        // Create Tenant
        $tenantUser = new Tenant();
        $tenantUser->setEmail('tenant@example.com');
        $tenantUser->setFirstName('Mike');
        $tenantUser->setLastName('Tenant');
        $tenantUser->setPhone('555-0103');
        $tenantUser->setPassword($this->passwordHasher->hashPassword($tenantUser, 'password'));
        $manager->persist($tenantUser);

        // Create Property
        $property = new Property();
        $property->setAddress('123 Main Street');
        $property->setCity('New York');
        $property->setState('NY');
        $property->setZipCode('10001');
        $property->setMonthlyRent('2500.00');
        $property->setBedrooms(3);
        $property->setBathrooms(2);
        $property->setSquareFeet(1500);
        $property->setDescription('Beautiful apartment in downtown with great views.');
        $property->setStatus('occupied');
        $property->setOwner($ownerUser);
        $property->setManager($managerUser);
        $manager->persist($property);

        // Create Lease
        $lease = new Lease();
        $lease->setStartDate(new \DateTime('2024-01-01'));
        $lease->setEndDate(new \DateTime('2024-12-31'));
        $lease->setMonthlyRent('2500.00');
        $lease->setSecurityDeposit('2500.00');
        $lease->setStatus('active');
        $lease->setProperty($property);
        $lease->setTenant($tenantUser);
        $manager->persist($lease);

        // Create another Property
        $property2 = new Property();
        $property2->setAddress('456 Oak Avenue');
        $property2->setCity('Los Angeles');
        $property2->setState('CA');
        $property2->setZipCode('90210');
        $property2->setMonthlyRent('3500.00');
        $property2->setBedrooms(4);
        $property2->setBathrooms(3);
        $property2->setSquareFeet(2200);
        $property2->setDescription('Spacious family home with pool and garden.');
        $property2->setStatus('available');
        $property2->setOwner($ownerUser);
        $property2->setManager($managerUser);
        $manager->persist($property2);

        $manager->flush();
    }
}