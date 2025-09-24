<?php

namespace App\Controller;

use App\Entity\Ads;
use App\Entity\AdsLike;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AdsLikeController extends AbstractController
{
    #[Route('/ads/{id}/like', name: 'app_ads_like_toggle', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function toggle(Ads $ads, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        // CSRF
        $token = $request->headers->get('X-CSRF-TOKEN') ?? $request->request->get('_token');
        if (!$this->isCsrfTokenValid('like' . $ads->getId(), (string) $token)) {
            return $this->json(['ok' => false, 'error' => 'Bad CSRF'], 400);
        }

        // (опционально) запрет лайкать своё объявление
        if ($ads->getUser() && $ads->getUser() === $user) {
            return $this->json(['ok' => false, 'error' => 'Нельзя лайкать своё объявление'], 403);
        }

        // найти существующий лайк
        $repo = $em->getRepository(AdsLike::class);
        $like = $repo->findOneBy(['ads' => $ads, 'user' => $user]);

        if ($like) {
            // снять лайк
            $em->remove($like);
            $em->flush();

            $count = (int) $em->createQuery('SELECT COUNT(l.id) FROM App\Entity\AdsLike l WHERE l.ads = :ad')
                ->setParameter('ad', $ads)
                ->getSingleScalarResult();

            return $this->json(['ok' => true, 'liked' => false, 'count' => $count]);
        }

        // поставить лайк
        $like = (new AdsLike())->setAds($ads)->setUser($user);
        $em->persist($like);
        $em->flush();

        $count = (int) $em->createQuery('SELECT COUNT(l.id) FROM App\Entity\AdsLike l WHERE l.ads = :ad')
            ->setParameter('ad', $ads)
            ->getSingleScalarResult();

        return $this->json(['ok' => true, 'liked' => true, 'count' => $count]);
    }
}
