<?php

namespace App\Controller;

use App\Entity\Articles;
use App\Entity\Documents;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArticleController extends AbstractController
{
    private const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
    ];
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SluggerInterface $slugger,
    ) {}

    #[Route('/article/create', name: 'article_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('⛔ У вас нет доступа к этой странице.');
        }

        $article = new Articles();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile[]|null $files */
            $files = $form->get('documents')->getData();

            if ($files) {
                $error = $this->handleUploads($files, $article);
                if ($error) {
                    $this->addFlash('error', $error);
                    return $this->render('article/list.html.twig', [
                        'form' => $form->createView(),
                        'articles' => $this->em->getRepository(Articles::class)->findBy([], ['id' => 'DESC']),
                    ]);
                }
            }

            $this->em->persist($article);
            $this->em->flush();

            $this->addFlash('success', '✅ Статья создана!');
            return $this->redirectToRoute('article_list');
        }

        return $this->render('article/list.html.twig', [
            'form' => $form->createView(),
            'articles' => $this->em->getRepository(Articles::class)->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/articles', name: 'article_list', methods: ['GET'])]
    public function list(): Response
    {
        $articles = $this->em->getRepository(Articles::class)->findBy([], ['id' => 'DESC']);
        return $this->render('article/index.html.twig', [
            'articles'   => $articles,
            'isLoggedIn' => $this->getUser() !== null,
        ]);
    }

    #[Route('/article/{id}', name: 'article_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Articles $article): Response
    {
        return $this->render('article/show.html.twig', ['article' => $article]);
    }

    #[Route('/article/{id}/delete', name: 'article_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Articles $article, Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('delete_article_' . $article->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', '❌ Ошибка CSRF.');
            return $this->redirectToRoute('article_list');
        }

        // удалить файлы с диска
        $fs  = new Filesystem();
        $dir = $this->getParameter('documents_directory');
        foreach ($article->getDocuments() as $doc) {
            $path = $dir . DIRECTORY_SEPARATOR . $doc->getPath();
            if ($fs->exists($path)) {
                $fs->remove($path);
            }
        }

        $this->em->remove($article);
        $this->em->flush();

        $this->addFlash('success', '🗑 Статья удалена.');
        return $this->redirectToRoute('article_list');
    }

    #[Route('/documents/{id}/download', name: 'document_download', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function download(Documents $document): Response
    {
        $filePath = $this->getParameter('documents_directory') . DIRECTORY_SEPARATOR . $document->getPath();
        if (!is_file($filePath)) {
            throw $this->createNotFoundException('Файл не найден.');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $document->getOriginalName() ?: $document->getPath()
        );
        $response->headers->set('Content-Type', $document->getType() ?: 'application/octet-stream');
        return $response;
    }

    #[Route('/documents/{id}/delete', name: 'document_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteDocument(Documents $document, Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('delete_document_' . $document->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', '❌ Ошибка CSRF.');
            return $this->redirectToRoute('article_show', ['id' => $document->getArticle()->getId()]);
        }

        $fs   = new Filesystem();
        $path = $this->getParameter('documents_directory') . DIRECTORY_SEPARATOR . $document->getPath();
        if ($fs->exists($path)) {
            $fs->remove($path);
        }

        $articleId = $document->getArticle()->getId();
        $this->em->remove($document);
        $this->em->flush();

        $this->addFlash('success', '🗑 Файл удалён.');
        return $this->redirectToRoute('article_show', ['id' => $articleId]);
    }

    /**
     * Возвращает текст ошибки (если есть) или null.
     * @param UploadedFile[] $files
     */
    private function handleUploads(array $files, Articles $article): ?string
    {
        $uploadDir = $this->getParameter('documents_directory');

        /** @var UploadedFile $file */
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            // размер
            if ($file->getSize() > self::MAX_SIZE_BYTES) {
                return sprintf(
                    'Файл "%s" превышает лимит %d МБ.',
                    $file->getClientOriginalName(),
                    self::MAX_SIZE_BYTES / (1024 * 1024)
                );
            }

            // mime
            $mime = (string) $file->getMimeType();
            if (!in_array($mime, self::ALLOWED_MIME, true)) {
                return sprintf('Недопустимый тип файла "%s" (%s).', $file->getClientOriginalName(), $mime);
            }

            // безопасное имя
            $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = $this->slugger->slug((string) $original)->lower();
            $ext      = $file->guessExtension()
                ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)
                ?: 'bin';
            $newName  = sprintf('%s-%s.%s', $safeName, uniqid('', true), $ext);

            try {
                $file->move($uploadDir, $newName);
            } catch (FileException $e) {
                return 'Ошибка при сохранении файла. Попробуйте ещё раз.';
            }

            $doc = new Documents();
            $doc->setPath($newName);
            $doc->setType($mime);
            $doc->setOriginalName($file->getClientOriginalName());
            $doc->setSize($file->getSize() ?: null);
            $doc->setArticle($article);

            $this->em->persist($doc);
            $article->addDocument($doc);
        }

        return null;
    }
}
