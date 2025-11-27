<?php

namespace App\Repository;

use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tenant>
 */
class TenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tenant::class);
    }

    /**
     * Find tenants with their active leases
     */
    public function findWithActiveLeases(): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.leases', 'l')
            ->addSelect('l')
            ->andWhere('l.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find tenant by ID with all related data
     */
    public function findWithDetails(int $id): ?Tenant
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.leases', 'l')
            ->leftJoin('l.property', 'p')
            ->leftJoin('t.rentPayments', 'rp')
            ->leftJoin('t.maintenanceRequests', 'mr')
            ->addSelect('l', 'p', 'rp', 'mr')
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find tenants by property
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.leases', 'l')
            ->innerJoin('l.property', 'p')
            ->andWhere('p.id = :propertyId')
            ->andWhere('l.status = :status')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find tenants with overdue rent payments
     */
    public function findWithOverdueRent(): array
    {
        $currentDate = new \DateTime();

        return $this->createQueryBuilder('t')
            ->innerJoin('t.rentPayments', 'rp')
            ->andWhere('rp.status = :status')
            ->andWhere('rp.dueDate < :currentDate')
            ->setParameter('status', 'pending')
            ->setParameter('currentDate', $currentDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get tenant payment statistics
     */
    public function getTenantPaymentStats(int $tenantId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                COUNT(rp.id) as total_payments,
                SUM(CASE WHEN rp.status = 'paid' THEN 1 ELSE 0 END) as paid_payments,
                SUM(CASE WHEN rp.status = 'pending' AND rp.dueDate < CURDATE() THEN 1 ELSE 0 END) as overdue_payments,
                AVG(CASE WHEN rp.status = 'paid' THEN DATEDIFF(rp.paidDate, rp.dueDate) ELSE NULL END) as avg_days_to_pay
            FROM tenant t
            LEFT JOIN rent_payment rp ON t.id = rp.tenant_id
            WHERE t.id = :tenantId
            GROUP BY t.id
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['tenantId' => $tenantId]);

        return $result->fetchAssociative() ?: [];
    }

    /**
     * Search tenants by name, email, or property address
     */
    public function searchTenants(string $query): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.leases', 'l')
            ->leftJoin('l.property', 'p')
            ->andWhere('t.firstName LIKE :query OR t.lastName LIKE :query OR t.email LIKE :query OR p.address LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find tenants without active leases
     */
    public function findWithoutActiveLeases(): array
    {
        $tenantsWithActiveLeases = $this->createQueryBuilder('t')
            ->select('t.id')
            ->innerJoin('t.leases', 'l')
            ->andWhere('l.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getArrayResult();

        $tenantIds = array_column($tenantsWithActiveLeases, 'id');

        if (empty($tenantIds)) {
            return $this->findAll();
        }

        return $this->createQueryBuilder('t')
            ->andWhere('t.id NOT IN (:tenantIds)')
            ->setParameter('tenantIds', $tenantIds)
            ->getQuery()
            ->getResult();
    }
}