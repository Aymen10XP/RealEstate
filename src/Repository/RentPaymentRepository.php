<?php

namespace App\Repository;

use App\Entity\RentPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RentPayment>
 */
class RentPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RentPayment::class);
    }

    /**
     * Find overdue rent payments
     */
    public function findOverduePayments(): array
    {
        $currentDate = new \DateTime();

        return $this->createQueryBuilder('rp')
            ->andWhere('rp.status = :status')
            ->andWhere('rp.dueDate < :currentDate')
            ->setParameter('status', 'pending')
            ->setParameter('currentDate', $currentDate)
            ->orderBy('rp.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find rent payments by month and year
     */
    public function findByMonthAndYear(int $month, int $year): array
    {
        $startDate = new \DateTime("$year-$month-01");
        $endDate = (clone $startDate)->modify('last day of this month');

        return $this->createQueryBuilder('rp')
            ->andWhere('rp.dueDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('rp.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find rent payments by lease
     */
    public function findByLease(int $leaseId): array
    {
        return $this->createQueryBuilder('rp')
            ->andWhere('rp.lease = :leaseId')
            ->setParameter('leaseId', $leaseId)
            ->orderBy('rp.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find rent payments by tenant
     */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('rp')
            ->andWhere('rp.tenant = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('rp.dueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                COUNT(*) as total_payments,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_payments,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                SUM(CASE WHEN status = 'pending' AND dueDate < CURDATE() THEN 1 ELSE 0 END) as overdue_payments,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_collected,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
                AVG(CASE WHEN status = 'paid' THEN DATEDIFF(paidDate, dueDate) ELSE NULL END) as avg_days_to_pay
            FROM rent_payment
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAssociative() ?: [];
    }

    /**
     * Get monthly collection report
     */
    public function getMonthlyCollectionReport(int $year): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                MONTH(dueDate) as month,
                COUNT(*) as total_payments,
                SUM(amount) as total_due,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_collected,
                SUM(CASE WHEN status = 'pending' AND dueDate < CURDATE() THEN amount ELSE 0 END) as total_overdue
            FROM rent_payment
            WHERE YEAR(dueDate) = :year
            GROUP BY MONTH(dueDate)
            ORDER BY month
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['year' => $year]);

        return $result->fetchAllAssociative();
    }

    /**
     * Find upcoming rent payments (due in the next 7 days)
     */
    public function findUpcomingPayments(): array
    {
        $currentDate = new \DateTime();
        $upcomingDate = (new \DateTime())->modify('+7 days');

        return $this->createQueryBuilder('rp')
            ->andWhere('rp.status = :status')
            ->andWhere('rp.dueDate BETWEEN :currentDate AND :upcomingDate')
            ->setParameter('status', 'pending')
            ->setParameter('currentDate', $currentDate)
            ->setParameter('upcomingDate', $upcomingDate)
            ->orderBy('rp.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate total collected rent for a specific period
     */
    public function getTotalCollectedForPeriod(\DateTime $startDate, \DateTime $endDate): float
    {
        $result = $this->createQueryBuilder('rp')
            ->select('SUM(rp.amount) as totalCollected')
            ->andWhere('rp.status = :status')
            ->andWhere('rp.paidDate BETWEEN :startDate AND :endDate')
            ->setParameter('status', 'paid')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    /**
     * Find rent payments with details
     */
    public function findWithDetails(): array
    {
        return $this->createQueryBuilder('rp')
            ->leftJoin('rp.lease', 'l')
            ->leftJoin('rp.tenant', 't')
            ->leftJoin('l.property', 'p')
            ->addSelect('l', 't', 'p')
            ->orderBy('rp.dueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search rent payments by tenant name or property address
     */
    public function searchPayments(string $query): array
    {
        return $this->createQueryBuilder('rp')
            ->leftJoin('rp.tenant', 't')
            ->leftJoin('rp.lease', 'l')
            ->leftJoin('l.property', 'p')
            ->andWhere('t.firstName LIKE :query OR t.lastName LIKE :query OR p.address LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }
}