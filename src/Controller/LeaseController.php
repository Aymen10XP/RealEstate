<?php

namespace App\Controller;

use App\Entity\Lease;
use App\Entity\Property;
use App\Form\LeaseType;
use App\Repository\LeaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/lease')]
class LeaseController extends AbstractController
{
    #[Route('/property/{id}/new', name: 'app_lease_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        // Check if manager has access to this property
        if ($property->getManager() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot add tenants to this property.');
        }

        $lease = new Lease();
        $lease->setProperty($property);

        $form = $this->createForm(LeaseType::class, $lease, ['property' => $property]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Update property status to occupied
                $property->setStatus('occupied');

                $entityManager->persist($lease);
                $entityManager->flush();

                $this->addFlash('success', 'Tenant added to property successfully!');
                return $this->redirectToRoute('app_property_show', ['id' => $property->getId()], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error adding tenant: ' . $e->getMessage());
            }
        }

        return $this->render('lease/new.html.twig', [
            'lease' => $lease,
            'property' => $property,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_lease_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lease $lease, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        // Check if manager has access to this lease's property
        if ($lease->getProperty()->getManager() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit this lease.');
        }

        $form = $this->createForm(LeaseType::class, $lease, ['property' => $lease->getProperty()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();

                $this->addFlash('success', 'Lease updated successfully!');
                return $this->redirectToRoute('app_property_show', ['id' => $lease->getProperty()->getId()], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating lease: ' . $e->getMessage());
            }
        }

        return $this->render('lease/edit.html.twig', [
            'lease' => $lease,
            'property' => $lease->getProperty(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_lease_delete', methods: ['POST'])]
    public function delete(Request $request, Lease $lease, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        // Check if manager has access to this lease's property
        if ($lease->getProperty()->getManager() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete this lease.');
        }

        $propertyId = $lease->getProperty()->getId();

        if ($this->isCsrfTokenValid('delete'.$lease->getId(), $request->request->get('_token'))) {
            try {
                // Update property status to available if this was the only active lease
                $property = $lease->getProperty();
                $activeLeases = $entityManager->getRepository(Lease::class)->findBy([
                    'property' => $property,
                    'status' => 'active'
                ]);

                if (count($activeLeases) <= 1) { // This lease is the only active one
                    $property->setStatus('available');
                }

                $entityManager->remove($lease);
                $entityManager->flush();

                $this->addFlash('success', 'Lease deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting lease: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_property_show', ['id' => $propertyId], Response::HTTP_SEE_OTHER);
    }
}