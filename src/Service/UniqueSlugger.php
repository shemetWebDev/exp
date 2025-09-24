<?php

namespace App\Service;

use App\Repository\UserPageRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Генерирует уникальный slug для сущности UserPage.
 */
class UniqueSlugger
{
    public function __construct(
        private SluggerInterface $slugger,
        private UserPageRepository $repo
    ) {}

    /**
     * Создаёт уникальный slug. Если slug уже занят,
     * добавляет -2, -3 и т.д.
     */
    public function makeUnique(string $text, ?int $excludeId = null): string
    {
        $base = strtolower($this->slugger->slug($text)->toString());
        $slug = $base !== '' ? $base : 'page';

        $i = 1;
        while ($this->repo->slugExists($slug, $excludeId)) {
            $i++;
            $slug = sprintf('%s-%d', $base, $i);
        }

        return $slug;
    }
}
