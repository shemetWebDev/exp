<?php

namespace App\Repository;

use App\Entity\Ads;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ads>
 *
 * @method Ads|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ads|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ads[]    findAll()
 * @method Ads[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ads::class);
    }

    /**
     * Фильтрация объявлений по категории/региону/поиску.
     */
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
            $qb->andWhere('LOWER(a.title) LIKE :q OR LOWER(a.description) LIKE :q OR a.poste_code LIKE :q2')
                ->setParameter('q', '%' . mb_strtolower($search) . '%')
                ->setParameter('q2', '%' . $search . '%');
        }

        // свежие сверху
        $qb->orderBy('a.created_at', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Популярные объявления: сортировка по кол-ву лайков (DESC), затем по дате (DESC).
     * Важно: используем маппинг OneToMany -> join по "a.likes".
     *
     * @return Ads[]
     */
    public function findPopular(int $limit = 12, ?\DateTimeImmutable $since = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.likes', 'l')
            ->addSelect('COUNT(l.id) AS HIDDEN likesCount')
            ->groupBy('a.id')
            ->orderBy('likesCount', 'DESC')
            ->addOrderBy('a.created_at', 'DESC')
            ->setMaxResults($limit);

        if ($since) {
            $qb->andWhere('a.created_at >= :since')
                ->setParameter('since', $since);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Последние добавленные.
     *
     * @return Ads[]
     */
    public function findLatest(int $limit = 12): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function incrementViews(int $adId): void
    {
        $this->createQueryBuilder('a')
            ->update()
            ->set('a.views', 'a.views + 1')
            ->andWhere('a.id = :id')
            ->setParameter('id', $adId)
            ->getQuery()
            ->execute();
    }
}
