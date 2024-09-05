<?php

namespace App\EventListener;

use App\Service\LocaleService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

final class LocaleRedirectListener
{
    private $router;
    private $localeService;

    public function __construct(RouterInterface $router, LocaleService $localeService)
    {
        $this->router = $router;
        $this->localeService = $localeService;
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // Exclude paths for static resources and vendor files
        if (preg_match('/^\/(assets|_wdt|_profiler)\//', $pathInfo)) {
            return;
        }

        // Check if locale is present in the URL
        if (!preg_match('/^\/(en|fr)(\/|$)/', $pathInfo)) {
            // Determine the preferred locale from the browser
            $preferredLocale = $this->localeService->getPreferredLocale($request);
            $request->attributes->set('preferredLocale', $preferredLocale);

            // Redirect to the URL with the default locale
            $url = '/' . $preferredLocale . $pathInfo;
            $response = new RedirectResponse($this->normalizeUrl($url));
            $event->setResponse($response);
        } else {
            $preferredLocale = $this->localeService->getPreferredLocale($request);
        }
        $request->attributes->set('preferred-locale', $preferredLocale);
    }

    private function normalizeUrl(string $url): string
    {
        // Remove any double slashes from the URL
        return preg_replace('/\/+/', '/', $url);
    }
}
