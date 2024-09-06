<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UuidType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'error_bubbling' => true,
                ])
            ->add('username', TextType::class, [
                'error_bubbling' => true,
            ])
            ->add('preferedLocale', ChoiceType::class, [
                'error_bubbling' => true,
                'choices' => [
                    'FR' => 'fr',
                    'EN' => 'en'
                ],
                'expanded' => false,
                'multiple' => false,
            ])
            // We don't want the user to be able to modify their sponsor code
            ->add('sponsorCode', TextType::class , [
                'error_bubbling' => true,
                'disabled' => true,
                'mapped' => false,
                'attr' => [
                    'readonly' => true,
                ],
                'data' => $options['data']->getSponsorCode(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
