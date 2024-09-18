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
     * Retrieves a single transaction for a specific user.
     *
     * This method fetches one transaction from the database for the given user.
     * It orders the transactions by date in descending order, so it will return
     * the most recent transaction if multiple exist.
     *
     * @param User $user The user for whom to retrieve the transaction
     * @return Transaction|null The most recent transaction for the user, or null if none found
     */
    public function findOneByUser(User $user): ?Transaction
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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

        $netAmount = $result['buyAmount'] - $result['sellAmount'];

        return $netAmount != 0.0 ? $netAmount : null;
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
    public function getCryptosOfUserWithBalance(User $user): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('c.id, c.name, c.symbol, c.coingecko_id,
                SUM(CASE WHEN t.transactionType = :buyType THEN t.cryptoAmount ELSE -t.cryptoAmount END) as cryptoBalance,
                SUM(CASE WHEN t.transactionType = :buyType THEN t.dollarAmount ELSE -t.dollarAmount END) as dollarBalance')
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
     * Retrieves the cryptocurrency information for a specific user and cryptocurrency.
     * Also add the balance of both crypto and dollar to the resulting array
     *
     * This method calculates the current balance of a specific cryptocurrency owned by the user,
     * including the total amount of crypto and the total dollar value invested.
     *
     * @param User $user The user for whom to retrieve the balance
     * @param string $coingeckoId The CoinGecko ID of the cryptocurrency
     * @return array|null An array containing id, name, symbol, coingecko_id, cryptoBalance, and dollarBalance, or null if not found
     */
    public function getCryptoBalanceForUserAndCrypto(User $user, string $coingeckoId): ?array
    {
        $result = $this->createQueryBuilder('t')
            ->select('c.id, c.name, c.symbol, c.coingecko_id,
                SUM(CASE WHEN t.transactionType = :buyType THEN t.cryptoAmount ELSE -t.cryptoAmount END) as cryptoBalance,
                SUM(CASE WHEN t.transactionType = :buyType THEN t.dollarAmount ELSE -t.dollarAmount END) as dollarBalance')
            ->join('t.cryptocurrency', 'c')
            ->where('t.user = :user')
            ->andWhere('c.coingecko_id = :coingeckoId')
            ->groupBy('c.id, c.name')
            ->setParameter('user', $user)
            ->setParameter('coingeckoId', $coingeckoId)
            ->setParameter('buyType', TransactionType::BUY)
            ->getQuery()
            ->getOneOrNullResult();

        return $result && $result['cryptoBalance'] > 0 ? $result : null;
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

    /**
     * Creates a QueryBuilder for retrieving all transactions for a given user and cryptocurrency by CoinGecko ID.
     *
     * We need to return a QueryBuilder because we need to use the Pagerfanta library to paginate the results.
     *
     * @param User $user The user for whom to retrieve the transactions
     * @param string $coingeckoId The CoinGecko ID of the cryptocurrency
     * @return QueryBuilder A QueryBuilder object for fetching the user's transactions for the specified cryptocurrency
     */
    public function getTransactionsForUserAndCoinGeckoId(User $user, string $coingeckoId): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->join('t.cryptocurrency', 'c')
            ->where('t.user = :user')
            ->andWhere('c.coingecko_id = :coingeckoId')
            ->setParameter('user', $user)
            ->setParameter('coingeckoId', $coingeckoId)
            ->orderBy('t.date', 'DESC');
    }
}
