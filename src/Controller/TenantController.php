<?php

namespace App\Controller;

use App\Entity\Tenant;
use App\Form\TenantType;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tenant')]
class TenantController extends AbstractController
{
    #[Route('/', name: 'app_tenant_index', methods: ['GET'])]
    public function index(TenantRepository $tenantRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        // For now, show all tenants. You might want to filter by manager's properties
        $tenants = $tenantRepository->findAll();

        return $this->render('tenant/index.html.twig', [
            'tenants' => $tenants,
        ]);
    }

    #[Route('/new', name: 'app_tenant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $tenant = new Tenant();
        $form = $this->createForm(TenantType::class, $tenant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($tenant);
                $entityManager->flush();

                $this->addFlash('success', 'Tenant created successfully.');
                return $this->redirectToRoute('app_tenant_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating tenant: ' . $e->getMessage());
            }
        }

        return $this->render('tenant/new.html.twig', [
            'tenant' => $tenant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_tenant_show', methods: ['GET'])]
    public function show(Tenant $tenant): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        return $this->render('tenant/show.html.twig', [
            'tenant' => $tenant,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tenant_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tenant $tenant, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $form = $this->createForm(TenantType::class, $tenant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Tenant updated successfully.');
                return $this->redirectToRoute('app_tenant_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating tenant: ' . $e->getMessage());
            }
        }

        return $this->render('tenant/edit.html.twig', [
            'tenant' => $tenant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_tenant_delete', methods: ['POST'])]
    public function delete(Request $request, Tenant $tenant, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        if ($this->isCsrfTokenValid('delete'.$tenant->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($tenant);
                $entityManager->flush();
                $this->addFlash('success', 'Tenant deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting tenant: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_tenant_index', [], Response::HTTP_SEE_OTHER);
    }
}