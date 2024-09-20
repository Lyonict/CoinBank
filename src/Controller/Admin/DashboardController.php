<?php

namespace App\Controller\Admin;

use App\Entity\Cryptocurrency;
use App\Entity\Transaction;
use App\Entity\User;
use App\Service\GlobalStateService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(private GlobalStateService $globalStateService)
    {
    }

    #[Route('/{_locale}/admin', name: 'app_admin_dashboard')]
    public function index(): Response
    {
        $lockdownStatus = $this->globalStateService->isLockdown() ? 'Enabled' : 'Disabled';
        return $this->render('admin/index.html.twig', [
            'lockdownStatus' => $lockdownStatus,
        ]);
        //return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('CoinBank');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Users', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Cryptocurrencies', 'fas fa-coins', Cryptocurrency::class);
        yield MenuItem::linkToCrud('Transactions', 'fas fa-file-invoice', Transaction::class);
    }

    #[Route('/{_locale}/admin/toggle-lockdown', name: 'admin_toggle_lockdown')]
    public function toggleLockdown(): Response
    {
        $currentState = $this->globalStateService->isLockdown();
        $this->globalStateService->setLockdown(!$currentState);

        return $this->redirectToRoute('app_admin_dashboard');
    }
}
