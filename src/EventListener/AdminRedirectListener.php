<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdminRedirectListener implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // Check if it's a controller class
        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof \App\Controller\UserController) {
            if ($this->security->isGranted('ROLE_ADMIN')) {
                // Redirect admin to a different route (change 'app_admin_dashboard' to your admin route)
                $event->setController(function() {
                    return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
                });
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}