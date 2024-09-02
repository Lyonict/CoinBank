<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BankFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', NumberType::class, [
                'mapped' => false,
                'error_bubbling' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
            ])
            ->add('bankTransactionMode', ChoiceType::class, [
                'mapped' => false,
                'choices' => [
                    'Deposit' => 'deposit',
                    'Withdraw' => 'withdraw',
                ],
                'error_bubbling' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Choice(['deposit', 'withdraw']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
