<?php

namespace App\Controller;

use App\Entity\UserPage;
use App\Form\UserPageType;
use App\Repository\UserPageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class UserPageController extends AbstractController
{
    #[Route('pageCreate/', name: 'user_page_create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $userPage = new UserPage();
        $userPage->setUser($user);
        $form = $this->createForm(UserPageType::class, $userPage);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bannerImgFile = $form->get('bannerImg')->getData();
            $imageFile = $form->get('image')->getData();
            if ($bannerImgFile) {
                $bannerImgFileName = uniqid() . '.' . $bannerImgFile->guessExtension();
                try {
                    $bannerImgFile->move(
                        $this->getParameter('uploads_directory'),
                        $bannerImgFileName
                    );
                    $userPage->setBannerImg($bannerImgFileName);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Ошибка при загрузке изображения баннера');
                    return $this->redirectToRoute('user_page_create');
                }
            }
            if ($imageFile) {
                $imageFileName = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $imageFileName
                    );
                    $userPage->setImage($imageFileName);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Ошибка при загрузке изображения');
                    return $this->redirectToRoute('user_page_create');
                }
            }

            $entityManager->persist($userPage);
            $entityManager->flush();

            $this->addFlash('success', 'Страница успешно создана!');

            return $this->redirectToRoute('app_profile');
        }


        return $this->render('user_page/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/{slug}', name: 'user_page_show')]
    public function show(UserPageRepository $userPageRepository, string $slug): Response
    {
        $userPage = $userPageRepository->findOneBy(['slug' => $slug]);

        if (!$userPage) {
            throw $this->createNotFoundException('Страница не найдена');
        }


        return $this->render('user_page/show.html.twig', [
            'userPage' => $userPage,
        ]);
    }


    #[Route('pageCreate/{id}/edit', name: 'user_page_edit')]
    public function edit(int $id, Request $request, UserPageRepository $userPageRepository, EntityManagerInterface $entityManager): Response
    {

        $userPage = $userPageRepository->find($id);

        if (!$userPage) {
            throw $this->createNotFoundException('Страница не найдена');
        }


        $form = $this->createForm(UserPageType::class, $userPage);


        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Страница успешно обновлена!');

            return $this->redirectToRoute('app_profile', ['id' => $userPage->getId()]);
        }

        return $this->render('user_page/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/pageCreate/{id}/delete', name: 'user_page_delete', methods: ['POST'])]
    public function deletePage(int $id, Request $request, UserPageRepository $userPageRepository, EntityManagerInterface $entityManager): Response
    {
        $userPage = $userPageRepository->find($id);

        if (!$userPage) {
            throw $this->createNotFoundException('Страница не найдена');
        }
        if ($this->isCsrfTokenValid('delete' . $userPage->getId(), $request->request->get('_token'))) {
            $entityManager->remove($userPage);
            $entityManager->flush();

            $this->addFlash('success', 'Страница успешно удалена');
        } else {
            $this->addFlash('error', 'Ошибка при удалении страницы');
        }

        return $this->redirectToRoute('app_profile');
    }
}
