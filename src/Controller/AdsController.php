<?php

namespace App\Controller;

use App\Entity\Ads;
use App\Enum\Payed;
use App\Form\AdsType;
use App\Repository\AdsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/ads')]
final class AdsController extends AbstractController
{
    #[Route(name: 'app_ads_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $category = $request->query->get('category');
        $region   = $request->query->get('region');
        $search   = $request->query->get('search');

        $page    = max(1, (int) $request->query->get('page', 1));
        $perPage = 20;

        $qb = $em->createQueryBuilder()
            ->select('a')
            ->from(Ads::class, 'a');

        // Фильтры
        if ($category) {
            $qb->andWhere('a.category = :category')->setParameter('category', $category);
        }
        if ($region) {
            $qb->andWhere('a.region = :region')->setParameter('region', $region);
        }

        // Поиск (имена свойств такие же, как в твоём репо: title/description/poste_code)
        if ($search) {
            $qb->andWhere('LOWER(a.title) LIKE :q OR LOWER(a.description) LIKE :q OR a.poste_code LIKE :q2')
                ->setParameter('q', '%' . mb_strtolower($search) . '%')
                ->setParameter('q2', '%' . $search . '%');
        }

        // --- Вычисляемые поля для сортировки (без поля photo/has_cover) ---
        $isPaid      = 'CASE WHEN a.status = :paid THEN 1 ELSE 0 END';
        $viewsClamp  = 'CASE WHEN a.views > 100 THEN 100 ELSE COALESCE(a.views, 0) END';
        $daysExpr    = 'DATE_DIFF(CURRENT_DATE(), a.created_at)'; // используем snake_case свойство
        $freshness14 = "(CASE WHEN {$daysExpr} <= 14 THEN (14 - {$daysExpr}) ELSE 0 END)";
        $hasPrice    = 'CASE WHEN a.price IS NOT NULL THEN 1 ELSE 0 END';

        // Лайки отдельным подзапросом
        $likesSub = '(SELECT COUNT(l.id) FROM App\Entity\AdsLike l WHERE l.ads = a)';

        $qb->addSelect("{$likesSub}    AS HIDDEN likes_cnt");
        $qb->addSelect("{$freshness14} AS HIDDEN freshness");
        $qb->addSelect("{$viewsClamp}  AS HIDDEN views_c");
        $qb->addSelect("{$isPaid}      AS HIDDEN is_paid");
        $qb->addSelect("{$hasPrice}    AS HIDDEN has_price");

        $qb->setParameter('paid', Payed::PAID);

        // Составной порядок сортировки (убрали has_cover)
        $qb->orderBy('is_paid',        'DESC')
            ->addOrderBy('freshness',   'DESC')
            ->addOrderBy('likes_cnt',   'DESC')
            ->addOrderBy('views_c',     'DESC')
            ->addOrderBy('has_price',   'DESC')
            ->addOrderBy('a.created_at', 'DESC')
            ->addOrderBy('a.id',        'DESC');

        // Пагинация
        $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $paginator = new DoctrinePaginator($qb, false);
        $ads       = iterator_to_array($paginator->getIterator());
        $total     = count($paginator);
        $pages     = (int) ceil($total / $perPage);
        $hasNext   = $page < $pages;

        return $this->render('ads/index.html.twig', [
            'ads'      => $ads,
            'category' => $category,
            'region'   => $region,
            'search'   => $search,
            'page'     => $page,
            'pages'    => $pages,
            'hasNext'  => $hasNext,
            'perPage'  => $perPage,
            'total'    => $total,
        ]);
    }

    #[Route('/new', name: 'app_ads_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $ad = new Ads();
        $ad->setStatus(Payed::UNPAID);
        $ad->setReating('0');
        $ad->setUser($this->getUser());
        $ad->setCreatedAt(new \DateTimeImmutable());
        $ad->setUpdatedAt(new \DateTimeImmutable());

        $form = $this->createForm(AdsType::class, $ad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile[] $files */
            $files = $form->get('images')->getData() ?? [];

            // лимит 5 фото
            $limitLeft = max(0, 5 - count($ad->getPhotos()));
            if (count($files) > $limitLeft) {
                $this->addFlash('danger', sprintf('Можно добавить максимум %d фото.', $limitLeft));
                return $this->render('ads/new.html.twig', [
                    'ad'   => $ad,
                    'form' => $form,
                ]);
            }

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/ads';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }

            foreach ($files as $file) {
                $safeBase = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $newName  = $safeBase . '-' . uniqid('', true) . '.' . $file->guessExtension();
                $file->move($uploadDir, $newName);
                $ad->addPhoto($newName);
            }

            $em->persist($ad);
            $em->flush();

            return $this->redirectToRoute('app_ads_index');
        }

        return $this->render('ads/new.html.twig', [
            'ad'   => $ad,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ads_show', methods: ['GET'])]
    public function show(Ads $ad, AdsRepository $adsRepository, EntityManagerInterface $em): Response
    {
        $adsRepository->incrementViews($ad->getId());
        $em->refresh($ad);

        return $this->render('ads/show.html.twig', [
            'ad' => $ad,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ads_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Ads $ad,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        if ($this->getUser() !== $ad->getUser()) {
            throw $this->createAccessDeniedException('Вы можете редактировать только свои объявления.');
        }

        $form = $this->createForm(AdsType::class, $ad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Удаление отмеченных фото
            $toRemove = $request->request->all('remove_photos') ?? [];
            if ($toRemove) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/ads';
                foreach ($toRemove as $fileName) {
                    if (in_array($fileName, $ad->getPhotos(), true)) {
                        $path = $uploadDir . '/' . $fileName;
                        if (is_file($path)) {
                            @unlink($path);
                        }
                        $ad->removePhoto($fileName);
                    }
                }
            }

            // Добавление новых фото (лимит 5)
            /** @var UploadedFile[] $files */
            $files = $form->get('images')->getData() ?? [];
            $limitLeft = max(0, 5 - count($ad->getPhotos()));
            if (count($files) > $limitLeft) {
                $this->addFlash('danger', sprintf('Можно добавить ещё максимум %d фото.', $limitLeft));
                return $this->render('ads/edit.html.twig', [
                    'ad'   => $ad,
                    'form' => $form,
                ]);
            }

            if ($files) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/ads';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0775, true);
                }
                foreach ($files as $file) {
                    $safeBase = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                    $newName  = $safeBase . '-' . uniqid('', true) . '.' . $file->guessExtension();
                    $file->move($uploadDir, $newName);
                    $ad->addPhoto($newName);
                }
            }

            $ad->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();

            return $this->redirectToRoute('app_ads_index');
        }

        return $this->render('ads/edit.html.twig', [
            'ad'   => $ad,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ads_delete', methods: ['POST'])]
    public function delete(Request $request, Ads $ad, EntityManagerInterface $em): Response
    {
        if ($this->getUser() !== $ad->getUser()) {
            throw $this->createAccessDeniedException('Вы можете удалять только свои объявления.');
        }

        if ($this->isCsrfTokenValid('delete' . $ad->getId(), $request->getPayload()->getString('_token'))) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/ads';
            foreach ($ad->getPhotos() as $fileName) {
                $path = $uploadDir . '/' . $fileName;
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            $em->remove($ad);
            $em->flush();
        }

        return $this->redirectToRoute('app_profile');
    }
}
