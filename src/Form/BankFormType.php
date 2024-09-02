<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BankFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cardNumber', TextType::class, [
                'mapped' => false,
                'error_bubbling' => true,
                'disabled' => true,
                'data' => '1234 5678 9012 3456',
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('expiryDate', TextType::class, [
                'mapped' => false,
                'error_bubbling' => true,
                'disabled' => true,
                'data' => '12/34',
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('cvc', IntegerType::class, [
                'mapped' => false,
                'error_bubbling' => true,
                'disabled' => true,
                'data' => 123,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
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
                'choice_attr' => [
                    'Deposit' => [
                        'class' => 'btn-check',
                        'checked' => true,
                    ],
                    'Withdraw' => ['class' => 'btn-check'],
                ],
                'multiple' => false,
                'expanded' => true,
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
