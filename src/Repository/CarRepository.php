<?php

namespace App\Repository;

use App\Entity\Car;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Car>
 */
class CarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Car::class);
    }

    public function countCarsByBrand(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.brand, COUNT(c.id) AS car_count')
            ->where('c.status = :published')
            ->setParameter('published', 'published')
            ->groupBy('c.brand')
            ->getQuery()
            ->getResult();
    }

    public function searchCars(array $filters, int $page, int $limit = 10)
    {
        $qb = $this->createQueryBuilder('c');

        // Only published cars
        $qb->andWhere('c.status = :published')
            ->setParameter('published', 'published');

        if (!empty($filters['brand'])) {
            $qb->andWhere('LOWER(c.brand) = LOWER(:brand)')
                ->setParameter('brand', $filters['brand']);
        }

        if (!empty($filters['model'])) {
            $qb->andWhere('c.model = :model')
                ->setParameter('model', $filters['model']);
        }

        if (!empty($filters['minYear'])) {
            $qb->andWhere('c.year >= :minYear')
                ->setParameter('minYear', $filters['minYear']);
        }

        if (!empty($filters['maxYear'])) {
            $qb->andWhere('c.year <= :maxYear')
                ->setParameter('maxYear', $filters['maxYear']);
        }

        if (!empty($filters['minVolume'])) {
            $qb->andWhere('c.volume >= :minVolume')
                ->setParameter('minVolume', $filters['minVolume']);
        }

        if (!empty($filters['maxVolume'])) {
            $qb->andWhere('c.volume <= :maxVolume')
                ->setParameter('maxVolume', $filters['maxVolume']);
        }

        if (!empty($filters['minRun'])) {
            $qb->andWhere('c.run >= :minRun')
                ->setParameter('minRun', $filters['minRun']);
        }

        if (!empty($filters['maxRun'])) {
            $qb->andWhere('c.run <= :maxRun')
                ->setParameter('maxRun', $filters['maxRun']);
        }

        if (!empty($filters['minPrice'])) {
            $qb->andWhere('c.price >= :minPrice')
                ->setParameter('minPrice', $filters['minPrice']);
        }

        if (!empty($filters['maxPrice'])) {
            $qb->andWhere('c.price <= :maxPrice')
                ->setParameter('maxPrice', $filters['maxPrice']);
        }

        $allowedSortFields = ['year', 'brand', 'model', 'volume', 'run', 'price'];
        if (!empty($filters['sort']) && in_array($filters['sort'], $allowedSortFields, true)) {
            $direction = strtoupper($filters['direction'] ?? 'DESC');
            if (!in_array($direction, ['ASC', 'DESC'])) {
                $direction = 'DESC';
            }
            if (in_array($filters['sort'], ['brand', 'model'])) {
                $qb->orderBy('LOWER(c.' . $filters['sort'] . ')', $direction);
            } else {
                $qb->orderBy('c.' . $filters['sort'], $direction);
            }
        } else {
            $qb->orderBy('c.year', 'DESC');
        }

        return $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }


    //    /**
    //     * @return Car[] Returns an array of Car objects
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

    //    public function findOneBySomeField($value): ?Car
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
