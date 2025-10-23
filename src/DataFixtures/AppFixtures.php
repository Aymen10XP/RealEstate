<?php


namespace App\DataFixtures;

use App\Entity\Payment;
use App\Entity\User;
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

    public function load(ObjectManager $manager, $property1, $lease1): void
    {
        // Create Admin user
        $admin = new User();
        $admin->setEmail('admin@realestate.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setPhone('123-456-7890');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // Create Manager user
        $managerUser = new User();
        $managerUser->setEmail('manager@realestate.com');
        $managerUser->setFirstName('Manager');
        $managerUser->setLastName('User');
        $managerUser->setPhone('123-456-7891');
        $managerUser->setRoles(['ROLE_MANAGER']);
        $managerUser->setPassword($this->passwordHasher->hashPassword($managerUser, 'manager123'));
        $manager->persist($managerUser);

        // Create Owner user
        $owner = new User();
        $owner->setEmail('owner@realestate.com');
        $owner->setFirstName('Property');
        $owner->setLastName('Owner');
        $owner->setPhone('123-456-7892');
        $owner->setRoles(['ROLE_OWNER']);
        $owner->setPassword($this->passwordHasher->hashPassword($owner, 'owner123'));
        $manager->persist($owner);

        // Create Tenant user
        $tenant = new User();
        $tenant->setEmail('tenant@realestate.com');
        $tenant->setFirstName('John');
        $tenant->setLastName('Tenant');
        $tenant->setPhone('123-456-7893');
        $tenant->setRoles(['ROLE_TENANT']);
        $tenant->setPassword($this->passwordHasher->hashPassword($tenant, 'tenant123'));
        $manager->persist($tenant);

        $manager->flush();

        // Create maintenance requests
        $maintenanceRequest = new MaintenanceRequest();
        $maintenanceRequest->setProperty($property1); // assuming you have properties
        $maintenanceRequest->setTenant($tenant);
        $maintenanceRequest->setTitle('Leaky faucet in kitchen');
        $maintenanceRequest->setDescription('The kitchen faucet has been dripping constantly for the past week.');
        $maintenanceRequest->setPriority('medium');
        $maintenanceRequest->setStatus('submitted');
        $manager->persist($maintenanceRequest);

// Create payments
        $payment = new Payment();
        $payment->setLease($lease1); // assuming you have leases
        $payment->setAmount('1500.00');
        $payment->setDueDate(new \DateTime('first day of next month'));
        $payment->setStatus('pending');
        $manager->persist($payment);
    }
}