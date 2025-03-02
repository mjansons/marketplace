<?php

namespace App\Repository;

use App\Entity\Computer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Computer>
 */
class ComputerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Computer::class);
    }

    public function searchComputers(array $filters, int $page, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('comp');

        // Only fetch published computers
        $qb->where('comp.status = :published')
            ->setParameter('published', 'published');

        if (!empty($filters['brand'])) {
            $qb->andWhere('LOWER(comp.brand) LIKE LOWER(:brand)')
                ->setParameter('brand', '%' . $filters['brand'] . '%');
        }
        if (!empty($filters['model'])) {
            $qb->andWhere('LOWER(comp.model) LIKE LOWER(:model)')
                ->setParameter('model', '%' . $filters['model'] . '%');
        }
        if (!empty($filters['ram'])) {
            $qb->andWhere('comp.ram = :ram')
                ->setParameter('ram', $filters['ram']);
        }
        if (!empty($filters['storage'])) {
            $qb->andWhere('comp.storage = :storage')
                ->setParameter('storage', $filters['storage']);
        }
        if (!empty($filters['productCondition'])) {
            $qb->andWhere('LOWER(comp.productCondition) = LOWER(:productCondition)')
                ->setParameter('productCondition', $filters['productCondition']);
        }
        if (!empty($filters['minPrice'])) {
            $qb->andWhere('comp.price >= :minPrice')
                ->setParameter('minPrice', $filters['minPrice']);
        }
        if (!empty($filters['maxPrice'])) {
            $qb->andWhere('comp.price <= :maxPrice')
                ->setParameter('maxPrice', $filters['maxPrice']);
        }

        // Sorting: only allow specific fields for safety
        $allowedSortFields = ['brand', 'model', 'ram', 'storage', 'price'];
        if (!empty($filters['sort']) && in_array($filters['sort'], $allowedSortFields, true)) {
            $direction = strtoupper($filters['direction'] ?? 'DESC');
            if (!in_array($direction, ['ASC', 'DESC'])) {
                $direction = 'DESC';
            }
            // For text fields, sort case-insensitively.
            if (in_array($filters['sort'], ['brand', 'model', 'productCondition'])) {
                $qb->orderBy('LOWER(comp.' . $filters['sort'] . ')', $direction);
            } else {
                $qb->orderBy('comp.' . $filters['sort'], $direction);
            }
        } else {
            // Default sort: by id descending
            $qb->orderBy('comp.id', 'DESC');
        }

        return $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Computer[] Returns an array of Computer objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Computer
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
