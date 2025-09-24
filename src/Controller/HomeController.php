<?php

namespace App\Controller;

use App\Repository\AdsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(AdsRepository $adsRepository): Response
    {
        // Топ-объявления по лайкам (за всё время). При желании можно ограничить периодом:
        // $popular = $adsRepository->findPopular(12, new \DateTimeImmutable('-90 days'));
        $popular = $adsRepository->findPopular(12);

        // На случай, если популярные пусты — можно отдать свежие (не обязательно)
        $latest = $adsRepository->findLatest(12);

        return $this->render('home/index.html.twig', [
            'popular' => $popular,
            'latest'  => $latest, // если используешь в шаблоне
        ]);
    }
}
