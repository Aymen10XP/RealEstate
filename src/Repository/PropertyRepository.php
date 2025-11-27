<?php

namespace App\Repository;

use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Property>
 */
class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    /**
     * Find properties with owner and manager details
     */
    public function findWithDetails(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.owner', 'o')
            ->leftJoin('p.manager', 'm')
            ->leftJoin('p.leases', 'l')
            ->addSelect('o', 'm', 'l')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find available properties (not occupied and not under maintenance)
     */
    public function findAvailableProperties(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'available')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find properties by city
     */
    public function findByCity(string $city): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.city = :city')
            ->setParameter('city', $city)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find properties by price range
     */
    public function findByPriceRange(float $minPrice, float $maxPrice): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.monthlyRent BETWEEN :minPrice AND :maxPrice')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find properties by bedroom count
     */
    public function findByBedrooms(int $bedrooms): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.bedrooms = :bedrooms')
            ->setParameter('bedrooms', $bedrooms)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search properties by address, city, or description
     */
    public function searchProperties(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.address LIKE :query OR p.city LIKE :query OR p.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get property statistics
     */
    public function getPropertyStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                COUNT(*) as total_properties,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_properties,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_properties,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_properties,
                AVG(monthlyRent) as average_rent,
                MAX(monthlyRent) as max_rent,
                MIN(monthlyRent) as min_rent
            FROM property
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAssociative() ?: [];
    }

    /**
     * Find properties with active maintenance requests
     */
    public function findWithActiveMaintenance(): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.maintenanceRequests', 'mr')
            ->andWhere('mr.status IN (:activeStatuses)')
            ->setParameter('activeStatuses', ['submitted', 'in_progress'])
            ->getQuery()
            ->getResult();
    }

    /**
     * Find properties without assigned manager
     */
    public function findWithoutManager(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.manager IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find properties by owner with financial summary
     */
    public function findByOwnerWithFinancials(int $ownerId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                p.*,
                COUNT(l.id) as active_leases,
                SUM(CASE WHEN rp.status = 'paid' THEN rp.amount ELSE 0 END) as collected_rent,
                SUM(CASE WHEN rp.status = 'pending' THEN rp.amount ELSE 0 END) as pending_rent
            FROM property p
            LEFT JOIN lease l ON p.id = l.property_id AND l.status = 'active'
            LEFT JOIN rent_payment rp ON l.id = rp.lease_id
            WHERE p.owner_id = :ownerId
            GROUP BY p.id
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['ownerId' => $ownerId]);

        return $result->fetchAllAssociative();
    }
}