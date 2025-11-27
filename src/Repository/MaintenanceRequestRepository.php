<?php

namespace App\Repository;

use App\Entity\MaintenanceRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaintenanceRequest>
 */
class MaintenanceRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaintenanceRequest::class);
    }

    /**
     * Find active maintenance requests
     */
    public function findActiveRequests(): array
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.status IN (:statuses)')
            ->setParameter('statuses', ['submitted', 'in_progress'])
            ->orderBy('mr.priority', 'DESC')
            ->addOrderBy('mr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find high priority maintenance requests
     */
    public function findHighPriorityRequests(): array
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.priority IN (:priorities)')
            ->andWhere('mr.status IN (:statuses)')
            ->setParameter('priorities', ['high', 'emergency'])
            ->setParameter('statuses', ['submitted', 'in_progress'])
            ->orderBy('mr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find maintenance requests by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.status = :status')
            ->setParameter('status', $status)
            ->orderBy('mr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find maintenance requests by priority
     */
    public function findByPriority(string $priority): array
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.priority = :priority')
            ->setParameter('priority', $priority)
            ->orderBy('mr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find maintenance request by ID with all related data
     */
    public function findWithDetails(int $id): ?MaintenanceRequest
    {
        return $this->createQueryBuilder('mr')
            ->leftJoin('mr.property', 'p')
            ->leftJoin('mr.tenant', 't')
            ->leftJoin('mr.assignedManager', 'm')
            ->addSelect('p', 't', 'm')
            ->andWhere('mr.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get maintenance statistics
     */
    public function getMaintenanceStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
                SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_requests,
                SUM(CASE WHEN priority = 'emergency' THEN 1 ELSE 0 END) as emergency_requests,
                SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_requests,
                AVG(CASE WHEN status = 'completed' THEN DATEDIFF(completedAt, createdAt) ELSE NULL END) as avg_completion_days
            FROM maintenance_request
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAssociative() ?: [];
    }

    /**
     * Find overdue maintenance requests (submitted more than 7 days ago)
     */
    public function findOverdueRequests(): array
    {
        $overdueDate = (new \DateTime())->modify('-7 days');

        return $this->createQueryBuilder('mr')
            ->andWhere('mr.status IN (:statuses)')
            ->andWhere('mr.createdAt < :overdueDate')
            ->setParameter('statuses', ['submitted', 'in_progress'])
            ->setParameter('overdueDate', $overdueDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find maintenance requests by property
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('mr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find maintenance requests by tenant
     */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.tenant = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('mr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find maintenance requests without assigned manager
     */
    public function findUnassignedRequests(): array
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.assignedManager IS NULL')
            ->andWhere('mr.status IN (:statuses)')
            ->setParameter('statuses', ['submitted', 'in_progress'])
            ->orderBy('mr.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }



    public function findByManager(\App\Entity\Manager $manager): array
    {
        return $this->createQueryBuilder('mr')
            ->innerJoin('mr.property', 'p')
            ->andWhere('p.manager = :manager')
            ->setParameter('manager', $manager)
            ->orderBy('mr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active maintenance requests by manager
     */
    public function findActiveByManager(\App\Entity\Manager $manager): array
    {
        return $this->createQueryBuilder('mr')
            ->innerJoin('mr.property', 'p')
            ->andWhere('p.manager = :manager')
            ->andWhere('mr.status IN (:statuses)')
            ->setParameter('manager', $manager)
            ->setParameter('statuses', ['submitted', 'in_progress'])
            ->orderBy('mr.priority', 'DESC')
            ->addOrderBy('mr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }






    /**
     * Search maintenance requests by title or description
     */
    public function searchRequests(string $query): array
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.title LIKE :query OR mr.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }
}