<?php

namespace App\Controller;

use App\Entity\Property;
use App\Form\PropertyType;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/property')]
class PropertyController extends AbstractController
{
    #[Route('/', name: 'app_property_index', methods: ['GET'])]
    public function index(PropertyRepository $propertyRepository): Response
    {
        $user = $this->getUser();
        $properties = [];

        if ($user instanceof \App\Entity\Manager) {
            $properties = $propertyRepository->findBy(['manager' => $user]);
        } elseif ($user instanceof \App\Entity\Owner) {
            $properties = $propertyRepository->findBy(['owner' => $user]);
        } else {
            throw $this->createAccessDeniedException('You cannot access this page.');
        }

        return $this->render('property/index.html.twig', [
            'properties' => $properties,
        ]);
    }

    #[Route('/new', name: 'app_property_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Only managers can create properties
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $property = new Property();
        $form = $this->createForm(PropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Set the current manager as the property manager
                $property->setManager($this->getUser());

                // You need to set an owner - for now, set it to the current user if they're an owner
                // Or you might want to add an owner selection field to the form
                $currentUser = $this->getUser();
                if ($currentUser instanceof \App\Entity\Owner) {
                    $property->setOwner($currentUser);
                } else {
                    // If current user is not an owner, you need to handle this case
                    // For now, let's get the first owner or create a default one
                    $ownerRepository = $entityManager->getRepository(\App\Entity\Owner::class);
                    $firstOwner = $ownerRepository->findOneBy([]);
                    if ($firstOwner) {
                        $property->setOwner($firstOwner);
                    } else {
                        $this->addFlash('error', 'No owner found. Please create an owner first.');
                        return $this->redirectToRoute('app_property_new');
                    }
                }

                $entityManager->persist($property);
                $entityManager->flush();

                $this->addFlash('success', 'Property created successfully.');
                return $this->redirectToRoute('app_property_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating property: ' . $e->getMessage());
            }
        }

        return $this->render('property/new.html.twig', [
            'property' => $property,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id}', name: 'app_property_show', methods: ['GET'])]
    public function show(Property $property): Response
    {
        $this->checkPropertyAccess($property);

        return $this->render('property/show.html.twig', [
            'property' => $property,
        ]);
    }




    #[Route('/{id}/edit', name: 'app_property_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');
        $this->checkPropertyAccess($property);

        $form = $this->createForm(PropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();

                $this->addFlash('success', 'Property updated successfully.');

                return $this->redirectToRoute('app_property_show', ['id' => $property->getId()], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating property: ' . $e->getMessage());
            }
        }

        return $this->render('property/edit.html.twig', [
            'property' => $property,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_property_delete', methods: ['POST'])]
    public function delete(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');
        $this->checkPropertyAccess($property);

        if ($this->isCsrfTokenValid('delete'.$property->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($property);
                $entityManager->flush();
                $this->addFlash('success', 'Property deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting property: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_property_index', [], Response::HTTP_SEE_OTHER);
    }

    private function checkPropertyAccess(Property $property): void
    {
        $user = $this->getUser();

        if ($user instanceof \App\Entity\Manager && $property->getManager() !== $user) {
            throw $this->createAccessDeniedException('You cannot access this property.');
        }

        if ($user instanceof \App\Entity\Owner && $property->getOwner() !== $user) {
            throw $this->createAccessDeniedException('You cannot access this property.');
        }
    }
}