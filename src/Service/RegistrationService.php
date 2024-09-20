<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;
use App\Service\LocaleService;

class RegistrationService
{
    public const PASSWORD_MIN_LENGTH = 8;
    public const PASSWORD_MAX_LENGTH = 32;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly UserRepository $userRepository,
        private readonly LocaleService $localeService
    ) {
    }

    public function registerUser(User $user, array $formData, Request $request): User
    {
        $hashedPassword = $this->userPasswordHasher->hashPassword(
            $user,
            $formData['plainPassword']
        );
        $user->setPassword($hashedPassword);

        $user->setSponsorCode(Uuid::v4());
        if(!in_array('ROLE_ADMIN', $user->getRoles())){
            $user->setBank(1000.0);
        };
        $user->setPreferedLocale($this->localeService->getPreferredLocale($request));
        $user->setIsFrozen(false);

        // Handle sponsor code
        $sponsorCode = $formData['sponsorCode'];
        if ($sponsorCode) {
            $sponsor = $this->userRepository->findOneBySponsorCode($sponsorCode);
            if ($sponsor) {
                $user->setSponsor($sponsor);
            }
        }

        // Persist the user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}