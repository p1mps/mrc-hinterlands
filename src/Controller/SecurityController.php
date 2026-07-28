<?php
namespace App\Controller;

use App\Form\RegistrationType;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        SecurityService $securityService
    ): Response {
        $user = new \App\Entity\User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $securityService->registerUser(
                $form->get('username')->getData(),
                $form->get('email')->getData(),
                $form->get('password')->getData(),
                $form->get('companyName')->getData(),
                $form->get('faction')->getData(),
            );

            $this->addFlash('success', 'Account created. Please log in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', ['form' => $form]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'error'         => $authUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void {}
}
