<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        // If no user is logged in, redirect to login
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();

        // Redirect based on user role
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('admin_dashboard');
        } elseif (in_array('ROLE_MANAGER', $user->getRoles())) {
            return $this->redirectToRoute('manager_dashboard');
        } elseif (in_array('ROLE_OWNER', $user->getRoles())) {
            return $this->redirectToRoute('owner_dashboard');
        } elseif (in_array('ROLE_TENANT', $user->getRoles())) {
            return $this->redirectToRoute('tenant_dashboard');
        }

        // Default fallback
        return $this->redirectToRoute('app_login');
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function adminDashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('dashboard/admin.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    #[Route('/manager/dashboard', name: 'manager_dashboard')]
    public function managerDashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        return $this->render('dashboard/manager.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    #[Route('/owner/dashboard', name: 'owner_dashboard')]
    public function ownerDashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_OWNER');

        return $this->render('dashboard/owner.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    #[Route('/tenant/dashboard', name: 'tenant_dashboard')]
    public function tenantDashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TENANT');

        return $this->render('dashboard/tenant.html.twig', [
            'user' => $this->getUser()
        ]);
    }
}