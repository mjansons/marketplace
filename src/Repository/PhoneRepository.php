<?php

namespace App\Repository;

use App\Entity\Phone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Phone>
 */
class PhoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Phone::class);
    }

    public function searchPhones(array $filters, int $page, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('p');

        // Only published phones
        $qb->where('p.status = :published')
            ->setParameter('published', 'published');

        if (!empty($filters['brand'])) {
            $qb->andWhere('LOWER(p.brand) LIKE LOWER(:brand)')
                ->setParameter('brand', '%' . $filters['brand'] . '%');
        }
        if (!empty($filters['model'])) {
            $qb->andWhere('LOWER(p.model) LIKE LOWER(:model)')
                ->setParameter('model', '%' . $filters['model'] . '%');
        }
        if (!empty($filters['memory'])) {
            $qb->andWhere('p.memory = :memory')
                ->setParameter('memory', $filters['memory']);
        }
        if (!empty($filters['productCondition'])) {
            $qb->andWhere('LOWER(p.productCondition) = LOWER(:productCondition)')
                ->setParameter('productCondition', $filters['productCondition']);
        }
        if (!empty($filters['minPrice'])) {
            $qb->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', $filters['minPrice']);
        }
        if (!empty($filters['maxPrice'])) {
            $qb->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', $filters['maxPrice']);
        }

        $allowedSortFields = ['brand', 'model', 'memory', 'price'];
        if (!empty($filters['sort']) && in_array($filters['sort'], $allowedSortFields, true)) {
            $direction = strtoupper($filters['direction'] ?? 'DESC');
            if (!in_array($direction, ['ASC','DESC'])) {
                $direction = 'DESC';
            }
            // For text fields, sort case-insensitively.
            if (in_array($filters['sort'], ['brand', 'model', 'productCondition'])) {
                $qb->orderBy('LOWER(p.' . $filters['sort'] . ')', $direction);
            } else {
                $qb->orderBy('p.' . $filters['sort'], $direction);
            }
        } else {
            // Default sort
            $qb->orderBy('p.id', 'DESC');
        }

        return $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Phone[] Returns an array of Phone objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Phone
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
