<?php

namespace App\Tests\Service;

use App\Service\LocaleService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class LocaleServiceTest extends TestCase
{
    private LocaleService $localeService;

    protected function setUp(): void
    {
        $this->localeService = new LocaleService('en', ['en', 'fr']);
    }

    public function testGetPreferredLocaleWithCookie(): void
    {
        $request = Request::create('/');
        $request->cookies->set('CB-prefered-locale', 'fr');

        $this->assertEquals('fr', $this->localeService->getPreferredLocale($request));
    }

    public function testGetPreferredLocaleWithAcceptLanguageHeader(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7');

        $this->assertEquals('fr', $this->localeService->getPreferredLocale($request));
    }

    public function testGetPreferredLocaleWithUnsupportedLanguage(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'de-DE,de;q=0.9');

        $this->assertEquals('en', $this->localeService->getPreferredLocale($request));
    }
}