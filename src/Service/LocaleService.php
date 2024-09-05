<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class LocaleService
{
    private $defaultLocale;
    private $supportedLocales;

    public function __construct(string $defaultLocale, array $supportedLocales = ['en', 'fr'])
    {
        $this->defaultLocale = $defaultLocale;
        $this->supportedLocales = $supportedLocales;
    }

    public function getPreferredLocale(Request $request): string
    {
        // Check if the CB-prefered-locale cookie is set
        $cookieLocale = $request->cookies->get('CB-prefered-locale');
        if ($cookieLocale && in_array($cookieLocale, $this->supportedLocales)) {
            return $cookieLocale;
        }

        foreach ($request->getLanguages() as $language) {
            $locale = substr($language, 0, 2);
            if (in_array($locale, $this->supportedLocales)) {
                return $locale;
            }
        }
        return $this->defaultLocale;
    }
}