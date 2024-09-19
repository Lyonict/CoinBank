<?php

namespace App\Validator;

use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidSponsorCodeValidator extends ConstraintValidator
{
    public function __construct(
        private UserRepository $userRepository,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack
    ) {
    }
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidSponsorCode) {
            throw new UnexpectedTypeException($constraint, ValidSponsorCode::class);
        }
        /* @var ValidSponsorCode $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $adminContext = $request->attributes->get('easyadmin_context');

        // Check if we're in the admin context and it's a new entity
        if ($adminContext instanceof AdminContext && $adminContext->getCrud()->getCurrentPage() === 'new') {
            // Skip validation for new users in admin
            return;
        }

        $sponsor = $this->userRepository->findOneBySponsorCode($value);

        if (!$sponsor) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
