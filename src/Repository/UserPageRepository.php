<?php

namespace App\Repository;

use App\Entity\UserPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<UserPage>
 *
 * Полезные методы:
 *  - slugExists(string $slug, ?int $excludeId = null): bool
 *  - findOnePublicBySlug(string $slug, ?\DateTimeImmutable $now = null): ?UserPage
 *  - findPublicList(int $page = 1, int $limit = 20, ?int $userId = null, ?string $search = null, ?\DateTimeImmutable $now = null): array{items: UserPage[], total: int}
 *  - findVisibleByOwner(int $ownerId, ?\DateTimeImmutable $now = null): UserPage[]
 */
class UserPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPage::class);
    }

    /**
     * Проверка существования слага (с исключением текущей записи при редактировании).
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId !== null) {
            $qb->andWhere('p.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Публично видимая страница по slug.
     * Видимость: isPaid = true ИЛИ trialEndsAt > NOW().
     */
    public function findOnePublicBySlug(string $slug, ?\DateTimeImmutable $now = null): ?UserPage
    {
        $now ??= new \DateTimeImmutable('now');

        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug);

        $this->addPublicVisibilityCondition($qb, $now);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Список публично видимых страниц с пагинацией и опциональным фильтром:
     * - по владельцу (userId)
     * - по поиску (в заголовке/подзаголовке/ключевых словах)
     *
     * @return array{items: UserPage[], total: int}
     */
    public function findPublicList(
        int $page = 1,
        int $limit = 20,
        ?int $userId = null,
        ?string $search = null,
        ?\DateTimeImmutable $now = null
    ): array {
        $now ??= new \DateTimeImmutable('now');

        $page = max(1, $page);
        $limit = max(1, min(100, $limit));

        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC');

        $this->addPublicVisibilityCondition($qb, $now);

        if ($userId !== null) {
            $qb->andWhere('IDENTITY(p.user) = :uid')
                ->setParameter('uid', $userId);
        }

        if ($search !== null && trim($search) !== '') {
            $searchLike = '%' . mb_strtolower(trim($search)) . '%';
            // Поиск по нескольким полям (LOWER для кросс-СУБД)
            $qb->andWhere('LOWER(p.title) LIKE :q OR LOWER(p.subtitle) LIKE :q OR LOWER(p.keywords) LIKE :q')
                ->setParameter('q', $searchLike);
        }

        // Пагинация
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb, true);

        return [
            'items' => iterator_to_array($paginator->getIterator(), false),
            'total' => count($paginator),
        ];
    }

    /**
     * Все видимые сейчас страницы конкретного владельца (без пагинации).
     */
    public function findVisibleByOwner(int $ownerId, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable('now');

        $qb = $this->createQueryBuilder('p')
            ->andWhere('IDENTITY(p.user) = :uid')
            ->setParameter('uid', $ownerId)
            ->orderBy('p.createdAt', 'DESC');

        $this->addPublicVisibilityCondition($qb, $now);

        return $qb->getQuery()->getResult();
    }

    /**
     * Базовая видимость для варианта A:
     *  - оплачено ИЛИ триал не истёк
     */
    private function addPublicVisibilityCondition(QueryBuilder $qb, \DateTimeImmutable $now): void
    {
        $qb->andWhere('(p.isPaid = 1) OR (p.trialEndsAt IS NOT NULL AND p.trialEndsAt > :now)')
            ->setParameter('now', $now);
    }
}
