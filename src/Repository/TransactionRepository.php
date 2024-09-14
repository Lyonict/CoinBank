<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Enum\TransactionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function getNetAmountByName(string $name): ?float
{
    $result = $this->createQueryBuilder('t')
        ->select('
            COALESCE(SUM(CASE WHEN t.transactionType = :buyType THEN t.cryptoAmount ELSE 0 END), 0) as buyAmount,
            COALESCE(SUM(CASE WHEN t.transactionType = :sellType THEN t.cryptoAmount ELSE 0 END), 0) as sellAmount
        ')
        ->join('t.cryptocurrency', 'c')
        ->andWhere('c.name = :name')
        ->setParameter('name', $name)
        ->setParameter('buyType', TransactionType::BUY)
        ->setParameter('sellType', TransactionType::SELL)
        ->getQuery()
        ->getOneOrNullResult();

    if ($result === null) {
        return null;
    }

    return $result['buyAmount'] - $result['sellAmount'];
}

    //    /**
    //     * @return Transaction[] Returns an array of Transaction objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Transaction
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
