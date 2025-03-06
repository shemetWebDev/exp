<?php

namespace App\Controller;

use App\Entity\Articles;
use App\Entity\Documents;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    #[Route('/article/create', name: 'article_create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('⛔ У вас нет доступа к этой странице.');
        }

        $article = new Articles();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) { 
            $files = $form->get('documents')->getData();

            if ($files) {
                foreach ($files as $file) {
                    if ($file) {
                        $newFilename = uniqid() . '.' . $file->guessExtension();

                        try {
                            $file->move(
                                $this->getParameter('documents_directory'), // ✅ Путь в services.yaml
                                $newFilename
                            );
                        } catch (FileException $e) {
                            $this->addFlash('error', '❌ Ошибка при загрузке файла!');
                            return $this->redirectToRoute('article_create');
                        }

                        $document = new Documents();
                        $document->setPath($newFilename);
                        $document->setType($file->getClientMimeType());
                        $document->setArticle($article);
                        $entityManager->persist($document);
                    }
                }
            }

            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', '✅ Статья создана!');
            return $this->redirectToRoute('article_create');
        }

        $articles = $entityManager->getRepository(Articles::class)->findAll();

        return $this->render('article/list.html.twig', [
            'form' => $form->createView(),
            'articles' => $articles
        ]);
    }

    #[Route('/articles', name: 'article_list')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $articles = $entityManager->getRepository(Articles::class)->findAll();
        $isLoggedIn = $this->getUser() !== null; 

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'isLoggedIn' => $isLoggedIn
        ]);
    }
}
