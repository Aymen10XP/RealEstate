<?php

namespace App\Controller;

use App\Entity\Property;
use App\Form\OwnerPropertyType;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/owner/property')]
class OwnerPropertyController extends AbstractController
{
    #[Route('/new', name: 'app_owner_property_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_OWNER');

        $property = new Property();
        $form = $this->createForm(OwnerPropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Set the current owner
                $property->setOwner($this->getUser());

                $entityManager->persist($property);
                $entityManager->flush();

                $this->addFlash('success', 'Property created successfully!');
                return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating property: ' . $e->getMessage());
            }
        }

        return $this->render('owner_property/new.html.twig', [
            'property' => $property,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_owner_property_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_OWNER');

        // Check if the property belongs to the current owner
        if ($property->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit this property.');
        }

        $form = $this->createForm(OwnerPropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Property updated successfully!');
                return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating property: ' . $e->getMessage());
            }
        }

        return $this->render('owner_property/edit.html.twig', [
            'property' => $property,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_owner_property_delete', methods: ['POST'])]
    public function delete(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_OWNER');

        // Check if the property belongs to the current owner
        if ($property->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete this property.');
        }

        if ($this->isCsrfTokenValid('delete'.$property->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($property);
                $entityManager->flush();
                $this->addFlash('success', 'Property deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting property: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
    }
}