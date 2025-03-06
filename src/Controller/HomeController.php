<?php

namespace App\Controller;

use App\Entity\Ads;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $entityManager): Response
    {

        $ads = $entityManager->getRepository(Ads::class)->findAll();
        return $this->render('home/index.html.twig', [
            'ads' => $ads,
        ]);
    }
}
