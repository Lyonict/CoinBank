<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class UserLocaleListener implements EventSubscriberInterface
{
    private $security;
    private $router;

    public function __construct(Security $security, RouterInterface $router)
    {
        $this->security = $security;
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -10],
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();
        $currentLocale = $request->getLocale();
        $preferredLocale = $user->getPreferedLocale();

        if ($preferredLocale && $currentLocale !== $preferredLocale) {
            $route = $request->attributes->get('_route');
            $routeParams = $request->attributes->get('_route_params', []);
            $routeParams['_locale'] = $preferredLocale;

            $url = $this->router->generate($route, $routeParams);
            $event->setResponse(new RedirectResponse($url));
        }
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();
        if (!$user instanceof User) {
            return;
        }

        $preferredLocale = $user->getPreferedLocale();
        if ($preferredLocale) {
            $event->getRequest()->setLocale($preferredLocale);
        }
    }
}