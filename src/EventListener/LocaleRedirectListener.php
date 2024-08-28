<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

final class LocaleRedirectListener
{
    private $router;
    private $defaultLocale;
    private $supportedLocales;

    public function __construct(RouterInterface $router, string $defaultLocale, array $supportedLocales = ['en', 'fr'])
    {
        $this->router = $router;
        $this->defaultLocale = $defaultLocale;
        $this->supportedLocales = $supportedLocales;
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

        // CHeck if locale is present in the URL
        if (!preg_match('/^\/(en|fr)(\/|$)/', $pathInfo)) {
            // Determine the preferred locale from the browser
            $preferredLocale = $this->getPreferredLocale($request->getLanguages());

            // Redirect to the URL with the default locale
            $url = '/' . $preferredLocale . $pathInfo;
            $response = new RedirectResponse($this->normalizeUrl($url));
            $event->setResponse($response);
        }
    }

    private function getPreferredLocale(array $acceptedLanguages): string
    {
        foreach ($acceptedLanguages as $language) {
            $locale = substr($language, 0, 2);
            if (in_array($locale, $this->supportedLocales)) {
                return $locale;
            }
        }
        return $this->defaultLocale;
    }

    private function normalizeUrl(string $url): string
    {
        // Remove any double slashes from the URL
        return preg_replace('/\/+/', '/', $url);
    }
}
