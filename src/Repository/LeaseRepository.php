<?php

namespace App\Repository;

use App\Entity\Lease;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lease>
 */
class LeaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lease::class);
    }

    /**
     * Find active leases for a tenant
     */
    public function findActiveLeasesByTenant(User $tenant): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.tenant = :tenant')
            ->andWhere('l.status = :status')
            ->andWhere('l.endDate >= :currentDate')
            ->setParameter('tenant', $tenant)
            ->setParameter('status', 'active')
            ->setParameter('currentDate', new \DateTime())
            ->orderBy('l.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find expired leases
     */
    public function findExpiredLeases(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.endDate < :currentDate')
            ->andWhere('l.status = :status')
            ->setParameter('currentDate', new \DateTime())
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find leases by property
     */
    public function findLeasesByProperty($propertyId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find upcoming lease expirations (within 30 days)
     */
    public function findUpcomingExpirations(): array
    {
        $thirtyDaysFromNow = new \DateTime('+30 days');

        return $this->createQueryBuilder('l')
            ->andWhere('l.endDate BETWEEN :now AND :future')
            ->andWhere('l.status = :status')
            ->setParameter('now', new \DateTime())
            ->setParameter('future', $thirtyDaysFromNow)
            ->setParameter('status', 'active')
            ->orderBy('l.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Lease[] Returns an array of Lease objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Lease
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}