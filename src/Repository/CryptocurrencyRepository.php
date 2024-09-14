<?php

namespace App\Repository;

use App\Entity\Cryptocurrency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cryptocurrency>
 */
class CryptocurrencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cryptocurrency::class);
    }

    /**
     * @return string[] Returns an array of all cryptocurrency coingecko_id
     */
    public function findCoinGeckoIds(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.coingecko_id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $coingeckoId The coingecko_id of the cryptocurrency
     * @return Cryptocurrency|null Returns a Cryptocurrency object or null if not found
     */
    public function findOneByCoingeckoId(string $coingeckoId): ?Cryptocurrency
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.coingecko_id = :coingeckoId')
            ->setParameter('coingeckoId', $coingeckoId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $name The name of the cryptocurrency
     * @return Cryptocurrency|null Returns a Cryptocurrency object or null if not found
     */
    public function findOneByName(string $name): ?Cryptocurrency
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Cryptocurrency[] Returns an array of Cryptocurrency objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Cryptocurrency
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
