<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\LocaleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RegistrationController extends AbstractController
{
    #[Route('/{_locale}/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        LocaleService $localeService
        ): Response
    {
        $user = $this->getUser();
        if($user) {
            return $this->redirectToRoute('app_user_dashboard');
        } else {
            $user = new User;
        }

        $passwordMinLength = 8;
        $passwordMaxLength = 32;

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'password_min_length' => $passwordMinLength,
            'password_max_length' => $passwordMaxLength,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            if(!in_array('ROLE_ADMIN', $user->getRoles())){
                $user->setBank(1000.0);
            };
            $user->setPreferedLocale($localeService->getPreferredLocale($request));

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'password_min_length' => $passwordMinLength,
            'password_max_length' => $passwordMaxLength,
        ]);
    }
}
