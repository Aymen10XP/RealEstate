<?php

namespace App\Repository;

use App\Entity\Owner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Owner>
 */
class OwnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Owner::class);
    }

    /**
     * Find owners with their properties count and total value
     */
    public function findWithPropertiesSummary(): array
    {
        return $this->createQueryBuilder('o')
            ->select('o', 'COUNT(p.id) as propertiesCount', 'SUM(p.monthlyRent) as totalMonthlyRent')
            ->leftJoin('o.properties', 'p')
            ->groupBy('o.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find owner by ID with all properties and related data
     */
    public function findWithDetails(int $id): ?Owner
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.properties', 'p')
            ->leftJoin('p.leases', 'l')
            ->leftJoin('l.tenant', 't')
            ->leftJoin('p.maintenanceRequests', 'mr')
            ->addSelect('p', 'l', 't', 'mr')
            ->andWhere('o.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get owner financial statistics
     */
    public function getOwnerFinancialStats(int $ownerId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                COUNT(p.id) as total_properties,
                SUM(p.monthlyRent) as total_potential_income,
                COUNT(DISTINCT l.id) as active_leases,
                SUM(CASE WHEN rp.status = 'paid' THEN rp.amount ELSE 0 END) as total_collected,
                SUM(CASE WHEN rp.status = 'pending' AND rp.dueDate < CURDATE() THEN rp.amount ELSE 0 END) as total_overdue
            FROM owner o
            LEFT JOIN property p ON o.id = p.owner_id
            LEFT JOIN lease l ON p.id = l.property_id AND l.status = 'active'
            LEFT JOIN rent_payment rp ON l.id = rp.lease_id
            WHERE o.id = :ownerId
            GROUP BY o.id
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['ownerId' => $ownerId]);

        return $result->fetchAssociative() ?: [];
    }

    /**
     * Find owners with the most properties
     */
    public function findTopOwners(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->select('o', 'COUNT(p.id) as propertiesCount')
            ->leftJoin('o.properties', 'p')
            ->groupBy('o.id')
            ->orderBy('propertiesCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search owners by name, email, or property address
     */
    public function searchOwners(string $query): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.properties', 'p')
            ->andWhere('o.firstName LIKE :query OR o.lastName LIKE :query OR o.email LIKE :query OR p.address LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find owners without properties
     */
    public function findWithoutProperties(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.properties', 'p')
            ->andWhere('p.id IS NULL')
            ->getQuery()
            ->getResult();
    }
}