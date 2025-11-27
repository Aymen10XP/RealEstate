<?php

namespace App\Controller;

use App\Entity\MaintenanceRequest;
use App\Form\MaintenanceRequestType;
use App\Repository\MaintenanceRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/maintenance')]
class MaintenanceRequestController extends AbstractController
{
    #[Route('/', name: 'app_maintenance_request_index', methods: ['GET'])]
    public function index(MaintenanceRequestRepository $maintenanceRequestRepository): Response
    {
        $user = $this->getUser();
        $requests = [];

        if ($user instanceof \App\Entity\Manager) {
            // Get all maintenance requests for properties managed by this manager
            $requests = $maintenanceRequestRepository->findByManager($user);
        } elseif ($user instanceof \App\Entity\Tenant) {
            $requests = $maintenanceRequestRepository->findBy(['tenant' => $user], ['createdAt' => 'DESC']);
        } else {
            throw $this->createAccessDeniedException('You cannot access maintenance requests.');
        }

        return $this->render('maintenance_request/index.html.twig', [
            'maintenance_requests' => $requests,
        ]);
    }

    #[Route('/new', name: 'app_maintenance_request_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TENANT');

        $maintenanceRequest = new MaintenanceRequest();
        $form = $this->createForm(MaintenanceRequestType::class, $maintenanceRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Set the current tenant
                $maintenanceRequest->setTenant($this->getUser());

                $entityManager->persist($maintenanceRequest);
                $entityManager->flush();

                $this->addFlash('success', 'Maintenance request submitted successfully.');
                return $this->redirectToRoute('app_maintenance_request_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error submitting maintenance request: ' . $e->getMessage());
            }
        }

        return $this->render('maintenance_request/new.html.twig', [
            'maintenance_request' => $maintenanceRequest,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_maintenance_request_show', methods: ['GET'])]
    public function show(MaintenanceRequest $maintenanceRequest): Response
    {
        $this->checkMaintenanceAccess($maintenanceRequest);

        return $this->render('maintenance_request/show.html.twig', [
            'maintenance_request' => $maintenanceRequest,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_maintenance_request_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MaintenanceRequest $maintenanceRequest, EntityManagerInterface $entityManager): Response
    {
        $this->checkMaintenanceAccess($maintenanceRequest);

        $form = $this->createForm(MaintenanceRequestType::class, $maintenanceRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // If status is changed to completed, set completed date
                if ($maintenanceRequest->getStatus() === 'completed' && !$maintenanceRequest->getCompletedAt()) {
                    $maintenanceRequest->setCompletedAt(new \DateTime());
                }

                // If status is changed from completed, clear completed date
                if ($maintenanceRequest->getStatus() !== 'completed' && $maintenanceRequest->getCompletedAt()) {
                    $maintenanceRequest->setCompletedAt(null);
                }

                $entityManager->flush();

                $this->addFlash('success', 'Maintenance request updated successfully.');

                return $this->redirectToRoute('app_maintenance_request_show', ['id' => $maintenanceRequest->getId()], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating maintenance request: ' . $e->getMessage());
            }
        }

        return $this->render('maintenance_request/edit.html.twig', [
            'maintenance_request' => $maintenanceRequest,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/done', name: 'app_maintenance_request_done', methods: ['POST'])]
    public function markAsDone(Request $request, MaintenanceRequest $maintenanceRequest, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        // Check if manager has access to this request's property
        $manager = $this->getUser();
        $property = $maintenanceRequest->getProperty();

        if ($property->getManager() !== $manager) {
            throw $this->createAccessDeniedException('You cannot mark this maintenance request as done.');
        }

        if ($this->isCsrfTokenValid('done'.$maintenanceRequest->getId(), $request->request->get('_token'))) {
            try {
                $maintenanceRequest->setStatus('completed');
                $maintenanceRequest->setCompletedAt(new \DateTime());
                $maintenanceRequest->setAssignedManager($manager);

                $entityManager->flush();

                $this->addFlash('success', 'Maintenance request marked as completed. The tenant has been notified.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error marking maintenance request as done: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_maintenance_request_index');
    }

    #[Route('/{id}', name: 'app_maintenance_request_delete', methods: ['POST'])]
    public function delete(Request $request, MaintenanceRequest $maintenanceRequest, EntityManagerInterface $entityManager): Response
    {
        $this->checkMaintenanceAccess($maintenanceRequest);

        if ($this->isCsrfTokenValid('delete'.$maintenanceRequest->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($maintenanceRequest);
                $entityManager->flush();

                $this->addFlash('success', 'Maintenance request deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting maintenance request: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_maintenance_request_index', [], Response::HTTP_SEE_OTHER);
    }

    private function checkMaintenanceAccess(MaintenanceRequest $maintenanceRequest): void
    {
        $user = $this->getUser();

        if ($user instanceof \App\Entity\Tenant && $maintenanceRequest->getTenant() !== $user) {
            throw $this->createAccessDeniedException('You cannot access this maintenance request.');
        }

        if ($user instanceof \App\Entity\Manager) {
            $property = $maintenanceRequest->getProperty();
            if ($property->getManager() !== $user) {
                throw $this->createAccessDeniedException('You cannot access this maintenance request.');
            }
        }

        // Allow owners to see maintenance requests for their properties
        if ($user instanceof \App\Entity\Owner) {
            $userProperties = $user->getProperties();
            $requestProperty = $maintenanceRequest->getProperty();

            $hasAccess = false;
            foreach ($userProperties as $property) {
                if ($property->getId() === $requestProperty->getId()) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                throw $this->createAccessDeniedException('You cannot access this maintenance request.');
            }
        }
    }
}