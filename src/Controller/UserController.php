<?php

namespace App\Controller;

use App\Form\BankFormType;
use App\Service\UserBankService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;

#[IsGranted('IS_AUTHENTICATED')]
#[Route('/{_locale}/user')]
class UserController extends AbstractController
{
    #[Route('', name: 'app_user_dashboard')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/bank', name: 'app_user_bank')]
    public function bank(Request $request, UserBankService $userBankService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(BankFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $amount = $form->get('amount')->getData();
            $bankTransactionMode = $form->get('bankTransactionMode')->getData();
            $userBankService->updateUserBank($user, $amount, $bankTransactionMode);

            return $this->redirectToRoute('app_user_bank');
        }

        return $this->render('user/bank.html.twig', [
            'bankForm' => $form,
        ]);
    }
}
