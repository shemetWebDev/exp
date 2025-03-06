<?php

namespace App\Repository;

use App\Entity\Ads;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ads>
 */
class AdsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ads::class);
    }

    public function filterAds(?string $category, ?string $region, ?string $search): array
    {
   $qb = $this->createQueryBuilder('a');

    if ($category) {
        $qb->andWhere('a.category = :category')
           ->setParameter('category', $category);
    }

    if ($region) {
        $qb->andWhere('a.region = :region')
           ->setParameter('region', $region);
    }

    if ($search) {
        $qb->andWhere('a.title LIKE :search OR a.description LIKE :search OR a.poste_code LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    return $qb->getQuery()->getResult();
    }
}
