<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

class UserCrudController extends AbstractCrudController
{
    private $passwordHasher;
    private $translator;
    public function __construct( UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator) {
        $this->passwordHasher = $passwordHasher;
        $this->translator = $translator;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

        public function createEntity(string $entityFqcn)
    {
        $user = new $entityFqcn();
        $user->setSponsorCode(Uuid::v4()->toRfc4122());
        return $user;
    }


    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPassword($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    private function hashPassword($user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $plainPassword = $user->getPassword();
        if ($plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            EmailField::new('email'),
            TextField::new('username'),
            TextField::new('password')
                ->hideOnIndex()
                ->hideOnDetail()
                ->onlyOnForms(),
            ChoiceField::new('roles')
                ->setChoices([
                    'User' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ])
                ->allowMultipleChoices()
                ->renderAsBadges(),
            NumberField::new('bank')
                ->setNumDecimals(2)
                ->setStoredAsString(false),
            ChoiceField::new('preferedLocale')
                ->setChoices([
                    'EN' => 'en',
                    'FR' => 'fr',
                ]),
            TextField::new('sponsorCode')
                ->hideOnIndex(),
            AssociationField::new('sponsor')
                ->setFormTypeOption('choice_label', 'username'),
            BooleanField::new('isFrozen')
                ->setLabel($this->translator->trans('Is Frozen')),
            CollectionField::new('sponsoredUsers')
                ->onlyOnDetail()
                ->setFormTypeOption('by_reference', false),
        ];

        $entity = $this->getContext()->getEntity()->getInstance();
        if (!$entity || null === $entity->getId()) {
            // This is a new entity, generate a new UUID
            $fields[] = TextField::new('sponsorCode')
                ->setFormTypeOption('data', Uuid::v4()->toRfc4122());
        } else {
            // This is an existing entity, use its current sponsorCode
            $fields[] = TextField::new('sponsorCode')
                ->setFormTypeOption('disabled', true);
            $fields[] = EmailField::new('email')
                ->setFormTypeOption('disabled', true)
                ->hideOnIndex()
                ->hideOnDetail()
                ->onlyOnForms();
            $fields[] = TextField::new('username')
                ->setFormTypeOption('disabled', true)
                ->hideOnIndex()
                ->hideOnDetail()
                ->onlyOnForms();
            $fields[] = TextField::new('password')
                ->setFormTypeOption('disabled', true)
                ->hideOnIndex()
                ->hideOnDetail()
                ->onlyOnForms();
        }

        return $fields;
    }
}
