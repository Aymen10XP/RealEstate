<?php

namespace App\Repository;

use App\Entity\Manager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Manager>
 */
class ManagerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Manager::class);
    }

    /**
     * Find managers with their assigned properties count
     */
    public function findWithPropertiesCount(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m', 'COUNT(p.id) as propertiesCount')
            ->leftJoin('m.properties', 'p')
            ->groupBy('m.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find managers by active status (having at least one active property)
     */
    public function findActiveManagers(): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.properties', 'p')
            ->andWhere('p.status IN (:activeStatuses)')
            ->setParameter('activeStatuses', ['occupied', 'available'])
            ->groupBy('m.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find manager by ID with related properties and maintenance requests
     */
    public function findWithDetails(int $id): ?Manager
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.properties', 'p')
            ->leftJoin('m.maintenanceRequests', 'mr')
            ->addSelect('p', 'mr')
            ->andWhere('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get manager performance statistics
     */
    public function getManagerPerformanceStats(int $managerId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                COUNT(p.id) as total_properties,
                SUM(CASE WHEN p.status = 'occupied' THEN 1 ELSE 0 END) as occupied_properties,
                SUM(CASE WHEN p.status = 'available' THEN 1 ELSE 0 END) as available_properties,
                COUNT(mr.id) as total_maintenance_requests,
                SUM(CASE WHEN mr.status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                AVG(CASE WHEN mr.status = 'completed' THEN DATEDIFF(mr.completedAt, mr.createdAt) ELSE NULL END) as avg_completion_days
            FROM manager m
            LEFT JOIN property p ON m.id = p.manager_id
            LEFT JOIN maintenance_request mr ON m.id = mr.assigned_manager_id
            WHERE m.id = :managerId
            GROUP BY m.id
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['managerId' => $managerId]);

        return $result->fetchAssociative() ?: [];
    }

    /**
     * Find available managers (with less than 10 properties)
     */
    public function findAvailableManagers(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m', 'COUNT(p.id) as propertiesCount')
            ->leftJoin('m.properties', 'p')
            ->groupBy('m.id')
            ->having('COUNT(p.id) < 10')
            ->getQuery()
            ->getResult();
    }
}