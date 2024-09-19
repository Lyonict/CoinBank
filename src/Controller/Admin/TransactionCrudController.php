<?php

namespace App\Controller\Admin;

use App\Entity\Transaction;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use App\Enum\TransactionType;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

class TransactionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Transaction::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('cryptocurrency')
                ->formatValue(function ($value, $entity) {
                    return $entity->getCryptocurrency() ? $entity->getCryptocurrency()->getName() : '';
                }),
            AssociationField::new('user')
                ->formatValue(function ($value, $entity) {
                    return $entity->getUser() ? $entity->getUser()->getEmail() : '';
                }),
            ChoiceField::new('transactionType')
                ->setChoices([
                    'Buy' => TransactionType::BUY,
                    'Sell' => TransactionType::SELL,
                ])
                ->renderAsBadges([
                    TransactionType::BUY->value => 'success',
                    TransactionType::SELL->value => 'danger',
                ]),
            NumberField::new('cryptoAmount'),
            NumberField::new('dollarAmount'),
            DateField::new('date'),
        ];
    }
}
