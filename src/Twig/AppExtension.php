<?php

namespace App\Twig;

use App\Service\GlobalStateService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(private GlobalStateService $globalStateService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_lockdown', [$this, 'isLockdown']),
        ];
    }

    public function isLockdown(): bool
    {
        return $this->globalStateService->isLockdown();
    }
}