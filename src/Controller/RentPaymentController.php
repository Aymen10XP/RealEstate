<?php

namespace App\Controller;

use App\Entity\RentPayment;
use App\Form\RentPaymentType;
use App\Repository\RentPaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/rent')]
class RentPaymentController extends AbstractController
{
    #[Route('/pay', name: 'app_rent_payment_pay', methods: ['GET', 'POST'])]
    public function payRent(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TENANT');

        $tenant = $this->getUser();
        $activeLease = $tenant->getActiveLease();

        if (!$activeLease) {
            $this->addFlash('error', 'You do not have an active lease.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Find or create the current month's rent payment
        $currentMonth = new \DateTime('first day of this month');
        $rentPayment = $entityManager->getRepository(RentPayment::class)->findOneBy([
            'lease' => $activeLease,
            'dueDate' => $currentMonth,
        ]);

        if (!$rentPayment) {
            $rentPayment = new RentPayment();
            $rentPayment->setLease($activeLease);
            $rentPayment->setTenant($tenant);
            $rentPayment->setAmount($activeLease->getMonthlyRent());
            $rentPayment->setDueDate($currentMonth);
            $rentPayment->setStatus('pending');
        }

        // If already paid, show message
        if ($rentPayment->getStatus() === 'paid') {
            $this->addFlash('info', 'Rent for this month has already been paid.');
            return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->createForm(RentPaymentType::class, $rentPayment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $rentPayment->setPaidDate(new \DateTime());
                $rentPayment->setStatus('paid');

                $entityManager->persist($rentPayment);
                $entityManager->flush();

                $this->addFlash('success', 'Rent payment submitted successfully!');
                return $this->redirectToRoute('app_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error processing payment: ' . $e->getMessage());
            }
        }

        return $this->render('rent_payment/pay.html.twig', [
            'rentPayment' => $rentPayment,
            'form' => $form->createView(),
            'activeLease' => $activeLease,
        ]);
    }

    #[Route('/history', name: 'app_rent_payment_history', methods: ['GET'])]
    public function paymentHistory(RentPaymentRepository $rentPaymentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TENANT');

        $payments = $rentPaymentRepository->findBy(
            ['tenant' => $this->getUser()],
            ['dueDate' => 'DESC']
        );

        return $this->render('rent_payment/history.html.twig', [
            'payments' => $payments,
        ]);
    }
}