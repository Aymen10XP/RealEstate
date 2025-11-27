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
     * Used to upgrade (rehash) the user's password automatically over time.
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
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all users by type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u INSTANCE OF :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all managers
     */
    public function findAllManagers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u INSTANCE OF App\Entity\Manager')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all owners
     */
    public function findAllOwners(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u INSTANCE OF App\Entity\Owner')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all tenants
     */
    public function findAllTenants(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u INSTANCE OF App\Entity\Tenant')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search users by name or email
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.firstName LIKE :query OR u.lastName LIKE :query OR u.email LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count users by type
     */
    public function countByType(string $type): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u INSTANCE OF :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(): array
    {
        return [
            'managers' => $this->countByType('App\Entity\Manager'),
            'owners' => $this->countByType('App\Entity\Owner'),
            'tenants' => $this->countByType('App\Entity\Tenant'),
            'total' => $this->count([]),
        ];
    }
}