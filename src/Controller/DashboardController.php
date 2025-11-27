<?php

namespace App\Controller;

use App\Entity\MaintenanceRequest;
use App\Entity\Property;
use App\Entity\RentPayment;
use App\Repository\MaintenanceRequestRepository;
use App\Repository\PropertyRepository;
use App\Repository\RentPaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        PropertyRepository $propertyRepository,
        MaintenanceRequestRepository $maintenanceRequestRepository,
        RentPaymentRepository $rentPaymentRepository
    ): Response {
        // Check if user is authenticated
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();

        if ($user instanceof \App\Entity\Manager) {
            return $this->managerDashboard($propertyRepository, $maintenanceRequestRepository);
        } elseif ($user instanceof \App\Entity\Owner) {
            return $this->ownerDashboard($propertyRepository);
        } elseif ($user instanceof \App\Entity\Tenant) {
            return $this->tenantDashboard($maintenanceRequestRepository, $rentPaymentRepository);
        }

        // If user type is not recognized, redirect to login
        return $this->redirectToRoute('app_login');
    }

    private function managerDashboard(PropertyRepository $propertyRepository, MaintenanceRequestRepository $maintenanceRequestRepository): Response
    {
        $properties = $propertyRepository->findBy(['manager' => $this->getUser()]);

        // Get maintenance requests assigned to this manager
        $maintenanceRequests = $maintenanceRequestRepository->findBy(
            ['assignedManager' => $this->getUser()],
            ['createdAt' => 'DESC'],
            5
        );

        return $this->render('dashboard/manager.html.twig', [
            'properties' => $properties,
            'maintenance_requests' => $maintenanceRequests, // Fixed variable name
        ]);
    }

    private function ownerDashboard(PropertyRepository $propertyRepository): Response
    {
        $properties = $propertyRepository->findBy(['owner' => $this->getUser()]);

        return $this->render('dashboard/owner.html.twig', [
            'properties' => $properties,
        ]);
    }

    private function tenantDashboard(MaintenanceRequestRepository $maintenanceRequestRepository, RentPaymentRepository $rentPaymentRepository): Response
    {
        $maintenanceRequests = $maintenanceRequestRepository->findBy(
            ['tenant' => $this->getUser()],
            ['createdAt' => 'DESC'],
            5
        );

        $rentPayments = $rentPaymentRepository->findBy(
            ['tenant' => $this->getUser()],
            ['dueDate' => 'DESC'],
            5
        );

        return $this->render('dashboard/tenant.html.twig', [
            'maintenance_requests' => $maintenanceRequests,
            'rent_payments' => $rentPayments,
        ]);
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        // If user is already logged in, redirect to dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('home/index.html.twig');
    }
}