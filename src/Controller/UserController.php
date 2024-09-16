<?php

namespace App\Controller;

use App\Form\BankFormType;
use App\Service\UserBankService;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;
use App\Form\CryptoTransactionFormType;
use App\Form\ProfileFormType;
use App\Repository\CryptocurrencyRepository;
use App\Repository\TransactionRepository;
use App\Service\CoinGeckoService;
use App\Service\CryptoFormService;
use App\Service\CryptoTransactionService;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/{_locale}/user')]
class UserController extends AbstractController
{
    private ?User $user = null;

    public function __construct(private readonly TranslatorInterface $translator, private readonly LoggerInterface $logger)
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
    public function index(TransactionRepository $transactionRepository, CoinGeckoService $coinGeckoService,): Response
    {
        $cryptoPrices = $coinGeckoService->getAllCryptoCurrentPrice();

        $cryptoBalances = $transactionRepository->getCryptoBalancesForUser($this->getAuthenticatedUser());

        $updatedCryptoBalances = [];
        foreach ($cryptoBalances as $balance) {
            if (isset($cryptoPrices[$balance['coingecko_id']])) {
                $balance['currentPrice'] = $cryptoPrices[$balance['coingecko_id']];
                $currentValue = $balance['cryptoBalance'] * $balance['currentPrice'];
                $balance['profitPercentage'] = round(($currentValue - $balance['dollarBalance']) / $balance['dollarBalance'] * 100, 2);
                if ($currentValue >= 1000000) {
                    $balance['currentValue'] = number_format($currentValue / 1000000, 2) . 'M';
                } elseif ($currentValue >= 1000) {
                    $balance['currentValue'] = number_format($currentValue / 1000, 2) . 'k';
                } else {
                    $balance['currentValue'] = number_format($currentValue, 2);
                }
                $updatedCryptoBalances[] = $balance;
            }
        }
        $cryptoBalances = $updatedCryptoBalances;

        return $this->render('user/dashboard.html.twig', [
            'cryptoBalances'=> $cryptoBalances,
        ]);
    }

    #[Route('/transactions', name: 'app_user_transactions')]
    public function transactions(Request $request, TransactionRepository $transactionRepository): Response
    {
        $transactions = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter($transactionRepository->getAllTransactionsForUser($this->getAuthenticatedUser())),
            $request->query->get('page', 1),
            10
        );

        return $this->render('user/transactions.html.twig', [
            'transactions' => $transactions,
        ]);
    }

    #[Route('/transactions/{coingecko_id}', name: 'app_user_transactions_crypto')]
    public function transactionsCrypto(
        Request $request,
        TransactionRepository $transactionRepository,
        CoinGeckoService $coinGeckoService,
        string $coingecko_id): Response
    {
        $cryptoInfo = $transactionRepository->getCryptoBalanceForUserAndCrypto($this->getAuthenticatedUser(), $coingecko_id);
        $cryptoInfo['currentPrice'] = $coinGeckoService->getCryptoCurrentPrice($coingecko_id);
        $currentValue = $cryptoInfo['cryptoBalance'] * $cryptoInfo['currentPrice'];
        $cryptoInfo['profitPercentage'] = round(($currentValue - $cryptoInfo['dollarBalance']) / $cryptoInfo['dollarBalance'] * 100, 2);
        $cryptoInfo['currentValue'] = $currentValue;
        $transactions = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter($transactionRepository->getTransactionForCoinGeckoIdForUser($this->getAuthenticatedUser(), $coingecko_id)),
            $request->query->get('page', 1),
            10
        );

        return $this->render('user/transactions-crypto.html.twig', [
            'transactions' => $transactions,
            'cryptoInfo' => $cryptoInfo,
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

    #[Route('/crypto-form', name: 'app_user_crypto_form', methods: ['GET', 'POST'])]
    public function cryptoForm(
        Request $request,
        CryptoFormService $cryptoFormService,
        CoinGeckoService $coinGeckoService
    ): Response
    {
        $form = $this->createForm(CryptoTransactionFormType::class);
        // Automatically select the cryptocurrency if the coingecko_id is provided
        $cryptoFormService->handleCryptoSelection($form, $request->query->get('crypto'));

        $form->handleRequest($request);

        $cryptoPrices = $coinGeckoService->getAllCryptoCurrentPrice();

        try {
            if ($cryptoFormService->processForm($form, $this->getAuthenticatedUser())) {
                return $this->redirectToRoute('app_user_dashboard');
            }
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translator->trans('An error occurred while creating the transaction.'));
        }

        return $this->render('user/crypto-form.html.twig', [
            'cryptoForm'=> $form,
            'cryptoPrices'=> $cryptoPrices,
        ]);
    }
}
