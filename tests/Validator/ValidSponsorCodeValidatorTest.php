<?php

namespace App\Tests\Validator;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Validator\ValidSponsorCode;
use App\Validator\ValidSponsorCodeValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidSponsorCodeValidatorTest extends TestCase
{
    /** @var UserRepository&\PHPUnit\Framework\MockObject\MockObject */
    private UserRepository $userRepository;

    /** @var TranslatorInterface&\PHPUnit\Framework\MockObject\MockObject */
    private TranslatorInterface $translator;

    /** @var ValidSponsorCodeValidator&\PHPUnit\Framework\MockObject\MockObject */
    private ValidSponsorCodeValidator $validator;

    /** @var ExecutionContextInterface&\PHPUnit\Framework\MockObject\MockObject */
    private ExecutionContextInterface $context;

    /** @var ConstraintViolationBuilderInterface&\PHPUnit\Framework\MockObject\MockObject */
    private ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = new ValidSponsorCodeValidator($this->userRepository, $this->translator);

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $this->validator->initialize($this->context);
    }

    public function testValidSponsorCode(): void
    {
        $constraint = new ValidSponsorCode();
        $sponsorCode = 'VALID_CODE';

        $this->userRepository->expects($this->once())
            ->method('findBySponsorCode')
            ->with($sponsorCode)
            ->willReturn(new User());

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($sponsorCode, $constraint);
    }

    public function testInvalidSponsorCode(): void
    {
        $constraint = new ValidSponsorCode();
        $sponsorCode = 'INVALID_CODE';

        $this->userRepository->expects($this->once())
            ->method('findBySponsorCode')
            ->with($sponsorCode)
            ->willReturn(null);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($this->violationBuilder);

        $this->violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $sponsorCode)
            ->willReturnSelf();

        $this->violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate($sponsorCode, $constraint);
    }

    public function testNullValue(): void
    {
        $constraint = new ValidSponsorCode();

        $this->userRepository->expects($this->never())
            ->method('findBySponsorCode');

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(null, $constraint);
    }

    public function testEmptyString(): void
    {
        $constraint = new ValidSponsorCode();

        $this->userRepository->expects($this->never())
            ->method('findBySponsorCode');

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('', $constraint);
    }

    public function testUnexpectedConstraintType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessageMatches('/Expected argument of type "App\\\\Validator\\\\ValidSponsorCode", "Mock_Constraint_[a-f0-9]+" given/');

        /** @var Constraint&MockObject $constraint */
        $constraint = $this->createMock(Constraint::class);
        $this->validator->validate('some_value', $constraint);
    }
}