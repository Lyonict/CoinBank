<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Upgrades the user's password hash to a newer, more secure version.
     *
     * This method is part of Symfony's password upgrading mechanism. It's called
     * automatically when a user logs in, allowing the application to upgrade
     * password hashes to newer algorithms over time without user intervention.
     *
     * @param PasswordAuthenticatedUserInterface $user The user whose password needs upgrading
     * @param string $newHashedPassword The new hashed password
     * @throws UnsupportedUserException If the user is not an instance of the User entity
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Finds a user by their unique sponsor code.
     *
     * This method is typically used during the registration process to associate
     * a new user with their sponsor. It queries the database for a user with the
     * given sponsor code.
     *
     * @param string $sponsorCode The unique sponsor code to search for
     * @return User|null Returns the User entity if found, or null if no user matches the sponsor code
     */
    public function findOneBySponsorCode(string $sponsorCode): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.sponsorCode = :sponsorCode')
            ->setParameter('sponsorCode', $sponsorCode)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
}
