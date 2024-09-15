<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\TransactionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * Calculates the net amount of a specific cryptocurrency by its name.
     *
     * This method computes the difference between the total bought and sold amounts
     * of a cryptocurrency, identified by its name. It considers all buy and sell
     * transactions in the database for the specified cryptocurrency.
     *
     * @param string $name The name of the cryptocurrency
     * @return float|null The net amount of the cryptocurrency, or null if no transactions found
     */
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

    /**
     * Retrieves the cryptocurrency balances for a specific user.
     *
     * This method calculates the current balance of each cryptocurrency owned by the user,
     * including the total amount of crypto and the total dollar value invested. It only
     * returns cryptocurrencies with a positive balance.
     *
     * @param User $user The user for whom to retrieve the balances
     * @return array An array of cryptocurrency balances, each containing id, name, symbol,
     *               coingecko_id, cryptoBalance, and dollarBalance
     */
    public function getCryptoBalancesForUser(User $user): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('c.id, c.name, c.symbol, c.coingecko_id,
                SUM(CASE WHEN t.transactionType = :buyType THEN t.cryptoAmount ELSE -t.cryptoAmount END) as cryptoBalance,
                SUM(t.dollarAmount) as dollarBalance')
            ->join('t.cryptocurrency', 'c')
            ->where('t.user = :user')
            ->groupBy('c.id, c.name')
            ->setParameter('user', $user)
            ->setParameter('buyType', TransactionType::BUY)
            ->getQuery()
            ->getResult();

        return array_filter($result, fn($crypto) => $crypto['cryptoBalance'] > 0);
    }

    /**
     * Creates a QueryBuilder for retrieving all transactions for a given user.
     *
     * We need to return a QueryBuilder because we need to use the Pagerfanta library to paginate the results.
     *
     * @param User $user The user for whom to retrieve the transactions
     * @return QueryBuilder A QueryBuilder object for fetching the user's transactions
     */
    public function getAllTransactionsForUser(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.date', 'DESC');
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
