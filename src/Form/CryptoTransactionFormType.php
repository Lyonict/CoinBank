<?php

namespace App\Form;

use App\Entity\Cryptocurrency;
use App\Entity\Transaction;
use App\Enum\TransactionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints as Assert;

class CryptoTransactionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cryptocurrency', EntityType::class, [
                'class' => Cryptocurrency::class,
                'choice_label' => 'name',
            ])
            ->add('transactionType', EnumType::class, [
                'class' => TransactionType::class,
                'choice_attr' => function($choice, $key, $value) {
                    $attributes = ['class' => 'btn-check'];
                    if ($value === 'buy') {
                        $attributes['checked'] = 'checked';
                    }
                    return $attributes;
                },
                'multiple' => false,
                'expanded' => true,
                'error_bubbling' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Choice([TransactionType::BUY, TransactionType::SELL]),
                ],
            ])
            ->add('cryptoAmount', NumberType::class, [
                'error_bubbling' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
            ])
            ->add('dollarAmount', NumberType::class, [
                'error_bubbling' => true,
                'mapped'=> false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
