<?php

namespace App\Controller;

use App\Entity\Ads;
use App\Enum\Payed;
use App\Form\AdsType;
use App\Repository\AdsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ads')]
final class AdsController extends AbstractController
{
    #[Route(name: 'app_ads_index', methods: ['GET'])]
    public function index(Request $request, AdsRepository $adsRepository): Response
    {

        $category = $request->query->get('category');
        $region = $request->query->get('region');
        $search = $request->query->get('search');

        if(!$category && !$region && !$search){ 
            $ads = $adsRepository->findAll();
        } else { 
            $ads = $adsRepository->filterAds($category, $region, $search);
        }

        return $this->render('ads/index.html.twig', [
            'ads' => $ads ,
            'category' => $category, 
            'region' => $region
        ]);
    }

    #[Route('/new', name: 'app_ads_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $ad = new Ads();
        $ad->getUser($user);
        $ad->setStatus(Payed::UNPAID);
        $ad->setReating(0);
        $ad->setUser($user);
        $ad->setCreatedAt(new \DateTimeImmutable());
        $ad->setUpdatedAt(new \DateTimeImmutable());
        $form = $this->createForm(AdsType::class, $ad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ad);
            $entityManager->flush();

            return $this->redirectToRoute('app_ads_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ads/new.html.twig', [
            'ad' => $ad,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ads_show', methods: ['GET'])]
    public function show(Ads $ad): Response
    {
        return $this->render('ads/show.html.twig', [
            'ad' => $ad,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ads_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ads $ad, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser('id') !== $ad->getUser('id')) {
            throw $this->createAccessDeniedException('Вы можете редактировать только свои объявления.');
        }

        $form = $this->createForm(AdsType::class, $ad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_ads_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ads/edit.html.twig', [
            'ad' => $ad,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ads_delete', methods: ['POST'])]
    public function delete(Request $request, Ads $ad, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser() !== $ad->getUser()) {
            throw $this->createAccessDeniedException('Вы можете удалять только свои объявления.');
        }

        if ($this->isCsrfTokenValid('delete'.$ad->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($ad);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
    }
}
