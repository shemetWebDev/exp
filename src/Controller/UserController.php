<?php

namespace App\Controller;

use App\Form\UserType;
use App\Repository\AdsRepository;
use App\Repository\UserPageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class UserController extends AbstractController
{
    #[Route('profile', name: 'app_profile', methods: ['GET'])]
    public function profile(AdsRepository $adsRepository, UserPageRepository $userPageRepository): Response
    {
        $user  = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $pages = $userPageRepository->findBy((['user' => $user]));
        $usersAds = $adsRepository->findBy((['user' => $user]));
        return $this->render(
            'user/index.html.twig',
            [
                'user' => $user,
                'usersAds' => $usersAds,
                'pages' => $pages,
            ]
        );
    }

    #[Route('/edit', name: 'app_edit_profil', methods: ['GET', 'POST'])]
    public function editProfil(Request $request, EntityManagerInterface $entityManagerInterface): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $entityManagerInterface->persist($user);
            $entityManagerInterface->flush();

            $this->addFlash('success', 'Профиль успешно обновлен!');
            return $this->redirectToRoute('app_profile');
        }


        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
