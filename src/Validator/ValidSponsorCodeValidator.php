<?php

namespace App\Validator;

use App\Repository\UserRepository;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidSponsorCodeValidator extends ConstraintValidator
{
    public function __construct(
        private UserRepository $userRepository,
        private readonly TranslatorInterface $translator
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

        $sponsor = $this->userRepository->findOneBySponsorCode($value);

        if (!$sponsor) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
