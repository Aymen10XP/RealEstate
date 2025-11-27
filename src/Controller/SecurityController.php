<?php

namespace App\Controller;

use App\Entity\Manager;
use App\Entity\Owner;
use App\Entity\Tenant;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If user is already logged in, redirect to dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        // If user is already logged in, redirect to dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userType = $data['user_type'];

            // Check if email already exists
            $existingUser = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                $this->addFlash('error', 'This email is already registered.');
                return $this->redirectToRoute('app_register');
            }

            switch ($userType) {
                case 'manager':
                    $user = new Manager();
                    $user->setRoles(['ROLE_MANAGER']);
                    break;
                case 'owner':
                    $user = new Owner();
                    $user->setRoles(['ROLE_OWNER']);
                    break;
                case 'tenant':
                    $user = new Tenant();
                    $user->setRoles(['ROLE_TENANT']);
                    break;
                default:
                    $this->addFlash('error', 'Invalid user type selected.');
                    return $this->redirectToRoute('app_register');
            }

            $user->setEmail($data['email']);
            $user->setFirstName($data['first_name']);
            $user->setLastName($data['last_name']);
            $user->setPhone($data['phone']);
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Registration successful! You can now log in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}