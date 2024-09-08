<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RegistrationService $registrationService,
    ) {
    }

    #[Route('/{_locale}/register', name: 'app_register')]
    public function register(
        Request $request,
        Security $security
        ): Response
    {
        $user = $this->getUser();
        if($user) {
            return $this->redirectToRoute('app_user_dashboard');
        } else {
            $user = new User();
        }

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'password_min_length' => RegistrationService::PASSWORD_MIN_LENGTH,
            'password_max_length' => RegistrationService::PASSWORD_MAX_LENGTH,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // As $form->getData() returns already a user object, we only recuperate what we need and send it to the service
            $formData = $form->all();
            $formData['plainPassword'] = $form->get('plainPassword')->getData();
            $formData['sponsorCode']= $form->get('sponsorCode')->getData();
            $this->registrationService->registerUser($user, $formData, $request);
            $security->login($user);
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'password_min_length' => RegistrationService::PASSWORD_MIN_LENGTH,
            'password_max_length' => RegistrationService::PASSWORD_MAX_LENGTH,
        ]);
    }
}
