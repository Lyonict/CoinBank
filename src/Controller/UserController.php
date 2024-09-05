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
use App\Form\ProfileFormType;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/{_locale}/user')]
class UserController extends AbstractController
{
    private ?User $user = null;

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    private function getAuthenticatedUser(): User
    {
        if ($this->user === null) {
            $this->user = $this->getUser();
            if (!$this->user instanceof User) {
                throw new \LogicException('The user is not authenticated.');
            }
        }
        return $this->user;
    }

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
        $form = $this->createForm(BankFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try{
                $amount = $form->get('amount')->getData();
                $bankTransactionMode = $form->get('bankTransactionMode')->getData();
                $userBankService->updateUserBank($this->getAuthenticatedUser(), $amount, $bankTransactionMode);
                $this->addFlash('success', $this->translator->trans('Your bank balance has been updated successfully.'));
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans('An error occurred while updating your bank balance.'));
            }

            return $this->redirectToRoute('app_user_bank');
        }

        return $this->render('user/bank.html.twig', [
            'bankForm' => $form,
        ]);
    }

    #[Route('/profile', name: 'app_user_profile')]
    public function profile(Request $request, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(ProfileFormType::class, $this->getAuthenticatedUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($this->getAuthenticatedUser());
            $manager->flush();
            $this->addFlash('success', $this->translator->trans('Your profile has been updated successfully.'));
            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/profile.html.twig', [
            'profileForm' => $form,
        ]);
    }
}
