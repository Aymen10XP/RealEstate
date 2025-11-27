<?php

namespace App\Repository;

use App\Entity\Lease;
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
     * Find active leases
     */
    public function findActiveLeases(): array
    {
        $currentDate = new \DateTime();

        return $this->createQueryBuilder('l')
            ->andWhere('l.status = :status')
            ->andWhere('l.startDate <= :currentDate')
            ->andWhere('l.endDate >= :currentDate')
            ->setParameter('status', 'active')
            ->setParameter('currentDate', $currentDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find leases expiring soon (within 30 days)
     */
    public function findExpiringSoon(): array
    {
        $currentDate = new \DateTime();
        $expiryDate = (new \DateTime())->modify('+30 days');

        return $this->createQueryBuilder('l')
            ->andWhere('l.status = :status')
            ->andWhere('l.endDate BETWEEN :currentDate AND :expiryDate')
            ->setParameter('status', 'active')
            ->setParameter('currentDate', $currentDate)
            ->setParameter('expiryDate', $expiryDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find lease by ID with all related data
     */
    public function findWithDetails(int $id): ?Lease
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.property', 'p')
            ->leftJoin('l.tenant', 't')
            ->leftJoin('l.rentPayments', 'rp')
            ->addSelect('p', 't', 'rp')
            ->andWhere('l.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find leases by property
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find leases by tenant
     */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.tenant = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get lease statistics
     */
    public function getLeaseStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                COUNT(*) as total_leases,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_leases,
                SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_leases,
                SUM(CASE WHEN status = 'terminated' THEN 1 ELSE 0 END) as terminated_leases,
                AVG(monthlyRent) as average_rent,
                AVG(DATEDIFF(endDate, startDate)) as average_lease_duration
            FROM lease
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAssociative() ?: [];
    }

    /**
     * Find leases that need renewal
     */
    public function findLeasesNeedingRenewal(): array
    {
        $renewalDate = (new \DateTime())->modify('+60 days');

        return $this->createQueryBuilder('l')
            ->andWhere('l.status = :status')
            ->andWhere('l.endDate <= :renewalDate')
            ->setParameter('status', 'active')
            ->setParameter('renewalDate', $renewalDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate total monthly rental income
     */
    public function getTotalMonthlyRentalIncome(): float
    {
        $result = $this->createQueryBuilder('l')
            ->select('SUM(l.monthlyRent) as totalIncome')
            ->andWhere('l.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    /**
     * Find leases with unpaid rent
     */
    public function findLeasesWithUnpaidRent(): array
    {
        $currentDate = new \DateTime();

        return $this->createQueryBuilder('l')
            ->innerJoin('l.rentPayments', 'rp')
            ->andWhere('l.status = :status')
            ->andWhere('rp.status = :paymentStatus')
            ->andWhere('rp.dueDate < :currentDate')
            ->setParameter('status', 'active')
            ->setParameter('paymentStatus', 'pending')
            ->setParameter('currentDate', $currentDate)
            ->getQuery()
            ->getResult();
    }
}