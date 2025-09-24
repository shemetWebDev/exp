<?php

namespace App\Controller;

use App\Entity\UserPage;
use App\Form\UserPageType;
use App\Repository\UserPageRepository;
use App\Service\UniqueSlugger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserPageController extends AbstractController
{
    private const TOTAL_STEPS = 5;                // 0..4
    private const SESSION_KEY = 'user_page.wizard';

    /** Соответствие полей шагам мастера — нужно, чтобы вернуть пользователя на «первый ошибочный» шаг */
    private const FIELD_STEP = [
        // step 0
        'title'          => 0,
        'slug'           => 0,
        'keywords'       => 0,
        'subtitle'       => 0,
        // step 1
        'bannerImg'      => 1,
        'image'          => 1,
        // step 2
        'advantageOne'   => 2,
        'advantageTwoo'  => 2,
        'advantageThree' => 2,
        // step 3
        'phone'          => 3,
        'email'          => 3,
        'companyName'    => 3,
        'adress'         => 3,
        // step 4
        'mapPosition'    => 4,
    ];

    /**
     * Мастер создания страницы (5 шагов, серверная валидация по шагам).
     */
    #[Route('/pageCreate', name: 'user_page_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UniqueSlugger $uniqueSlugger
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $session = $request->getSession();
        $saved   = $session->get(self::SESSION_KEY, []); // промежуточные данные + имена загруженных файлов

        // Текущий шаг и действие
        $step = (int) $request->request->get('step', 0);
        $nav  = (string) $request->request->get('nav', ''); // '', 'next', 'prev', 'submit'
        $step = max(0, min(self::TOTAL_STEPS - 1, $step));

        // Восстановим сущность из сессии
        $page = new UserPage();
        $page->setUser($user);
        $this->hydrateFromSession($page, $saved);

        // Форма с группами валидации для ТЕКУЩЕГО шага
        $form = $this->createForm(UserPageType::class, $page, [
            'validation_groups' => $this->groupsForStep($step),
        ]);
        $form->handleRequest($request);

        // НАЗАД — без валидации
        if ($form->isSubmitted() && $nav === 'prev') {
            $step = max(0, $step - 1);
            return $this->renderFormWizard($page, $form, $step);
        }

        // ДАЛЕЕ — валидируем только текущий шаг
        if ($form->isSubmitted() && $nav === 'next') {
            if ($form->isValid()) {
                // Шаг 1 (индекс 1) — изображения: сразу переносим файлы и кладём имена в сессию
                if ($step === 1) {
                    $this->storeUploadsToSession($request, $form, $saved);
                }
                $session->set(self::SESSION_KEY, $this->pageToSessionArray($page, $saved));
                $step = min(self::TOTAL_STEPS - 1, $step + 1);
            }
            return $this->renderFormWizard($page, $form, $step);
        }

        // СОЗДАТЬ — валидируем ВСЕ поля формы (а не сущность)
        if ($form->isSubmitted() && $nav === 'submit') {
            if ($step === 1) {
                // на всякий случай подхватить заменённые файлы, если были
                $this->storeUploadsToSession($request, $form, $saved);
            }
            // сохраним актуальные данные в сессию и зальём их обратно в сущность
            $session->set(self::SESSION_KEY, $this->pageToSessionArray($page, $saved));
            $this->hydrateFromSession($page, $session->get(self::SESSION_KEY, []));

            // Полная форма без ограничений групп — валидируются все constraints из FormType
            $finalForm = $this->createForm(UserPageType::class, $page);
            // Подадим те же данные, что пришли (clearMissing=false, чтобы не затирать отсутствующие поля)
            $finalForm->submit($request->request->all()[$form->getName()] ?? [], false);

            if (!$finalForm->isValid()) {
                $badStep = $this->firstInvalidStepFromForm($finalForm);
                return $this->renderFormWizard($page, $finalForm, $badStep);
            }

            // Уникальный slug
            if (!$page->getSlug()) {
                $page->setSlug($uniqueSlugger->makeUnique((string) $page->getTitle()));
            } else {
                $page->setSlug($uniqueSlugger->makeUnique($page->getSlug()));
            }

            // Имёна файлов из сессии (загружены на шаге 2)
            if (!empty($saved['bannerImg'])) {
                $page->setBannerImg($saved['bannerImg']);
            }
            if (!empty($saved['image'])) {
                $page->setImage($saved['image']);
            }

            $em->persist($page);
            $em->flush();

            // Очистить сессию мастера
            $session->remove(self::SESSION_KEY);

            $this->addFlash('success', 'Страница успешно создана!');
            return $this->redirectToRoute('app_profile');
        }

        // Первый показ формы (GET) или любой иной сценарий
        return $this->renderFormWizard($page, $form, $step);
    }

    /**
     * Публичный просмотр страницы (доступна если оплачена или триал ещё идёт).
     */
    #[Route('/page/{slug}', name: 'user_page_show', methods: ['GET'])]
    public function show(UserPageRepository $repo, string $slug): Response
    {
        $userPage = $repo->findOnePublicBySlug($slug);
        if (!$userPage) {
            throw $this->createNotFoundException('Страница недоступна или не существует.');
        }

        return $this->render('user_page/show.html.twig', [
            'userPage' => $userPage,
        ]);
    }

    /**
     * Редактирование страницы владельцем (без пошагового мастера).
     */
    #[Route('/pageCreate/{id}/edit', name: 'user_page_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        UserPageRepository $repo,
        EntityManagerInterface $em,
        UniqueSlugger $uniqueSlugger
    ): Response {
        $userPage = $repo->find($id);
        if (!$userPage) {
            throw $this->createNotFoundException('Страница не найдена.');
        }

        // Если есть Voter — оставляем проверку
        $this->denyAccessUnlessGranted('EDIT', $userPage);

        $form = $this->createForm(UserPageType::class, $userPage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Уникализируем slug при изменении
            $userPage->setSlug(
                $uniqueSlugger->makeUnique($userPage->getSlug(), $userPage->getId())
            );

            // Обработка замен изображений (при редактировании)
            $this->handleUploads($form, $userPage);

            $em->flush();
            $this->addFlash('success', 'Страница обновлена!');
            return $this->redirectToRoute('app_profile');
        }

        // Если в сущности есть getTrialEndsAt — отдадим его в шаблон; иначе — просто +48ч
        $trialEndsAt = \method_exists($userPage, 'getTrialEndsAt') && $userPage->getTrialEndsAt()
            ? $userPage->getTrialEndsAt()
            : new \DateTimeImmutable('+48 hours');

        return $this->render('user_page/edit.html.twig', [
            'form'        => $form->createView(),
            'trialEndsAt' => $trialEndsAt,
        ]);
    }

    /**
     * Удаление страницы.
     */
    #[Route('/pageCreate/{id}/delete', name: 'user_page_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        UserPageRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $userPage = $repo->find($id);
        if (!$userPage) {
            throw $this->createNotFoundException('Страница не найдена.');
        }

        if ($this->isCsrfTokenValid('delete' . $userPage->getId(), $request->request->get('_token'))) {
            $em->remove($userPage);
            $em->flush();
            $this->addFlash('success', 'Страница удалена.');
        } else {
            $this->addFlash('error', 'Ошибка CSRF при удалении.');
        }

        return $this->redirectToRoute('app_profile');
    }

    // ========================= ВСПОМОГАТЕЛЬНЫЕ =========================

    /** Группы валидации по шагу (0..4) */
    private function groupsForStep(int $step): array
    {
        return match ($step) {
            0 => ['step1'], // Основное
            1 => ['step2'], // Изображения
            2 => ['step3'], // Преимущества
            3 => ['step4'], // Контакты
            4 => ['step5'], // Карта
            default => ['step1'],
        };
    }

    /** Рендер шага мастера */
    private function renderFormWizard(UserPage $page, FormInterface $form, int $step): Response
    {
        // Если в сущности нет trialEndsAt — подставим "+48 часов" для вывода в шаблон
        $trialEndsAt = \method_exists($page, 'getTrialEndsAt') && $page->getTrialEndsAt()
            ? $page->getTrialEndsAt()
            : new \DateTimeImmutable('+48 hours');

        return $this->render('user_page/index.html.twig', [
            'form'        => $form->createView(),
            'trialEndsAt' => $trialEndsAt,
            'trial_hours' => 48,
            'step'        => $step,
            'stored'      => $this->pageToSessionArray($page), // для превью изображений
        ]);
    }

    /**
     * Сохранение загруженных файлов на шаге «Изображения» и запись имён в сессию.
     * Важно: FileType забывает файл при ре-рендере, поэтому двигаем файлы сразу при успешном шаге.
     */
    private function storeUploadsToSession(Request $request, FormInterface $form, array &$saved): void
    {
        $uploadDir = $this->getParameter('uploads_directory'); // см. services.yaml
        foreach (['bannerImg' => 'bannerImg', 'image' => 'image'] as $field => $key) {
            $file = $form->get($field)->getData();
            if ($file) {
                $newName = uniqid('', true) . '.' . $file->guessExtension();
                try {
                    $file->move($uploadDir, $newName);
                    $saved[$key] = $newName;
                } catch (FileException $e) {
                    $this->addFlash('error', "Ошибка при загрузке файла «{$field}».");
                }
            }
        }
        $request->getSession()->set(self::SESSION_KEY, array_merge(
            $request->getSession()->get(self::SESSION_KEY, []),
            $saved
        ));
    }

    /** Плоский массив для сохранения состояния в сессии */
    private function pageToSessionArray(UserPage $p, array $saved = []): array
    {
        return array_merge($saved, [
            'title'          => $p->getTitle(),
            'slug'           => $p->getSlug(),
            'keywords'       => $p->getKeywords(),
            'subtitle'       => $p->getSubtitle(),
            'advantageOne'   => $p->getAdvantageOne(),
            'advantageTwoo'  => $p->getAdvantageTwoo(),
            'advantageThree' => $p->getAdvantageThree(),
            'phone'          => $p->getPhone(),
            'adress'         => $p->getAdress(),
            'email'          => $p->getEmail(),
            'companyName'    => $p->getCompanyName(),
            'mapPosition'    => $p->getMapPosition(),
            // 'bannerImg' и 'image' лежат в $saved (если были загружены)
        ]);
    }

    /** Заполнение сущности из сохранённого массива (для повторного рендера) */
    private function hydrateFromSession(UserPage $p, array $data): void
    {
        // не трогаем поле, если ключа нет или значение null — чтобы не передавать null в string-сеттер
        if (array_key_exists('title', $data) && $data['title'] !== null) {
            $p->setTitle($data['title']);
        }
        if (array_key_exists('slug', $data) && $data['slug'] !== null) {
            $p->setSlug($data['slug']);
        }
        if (array_key_exists('keywords', $data) && $data['keywords'] !== null) {
            $p->setKeywords($data['keywords']);
        }
        if (array_key_exists('subtitle', $data) && $data['subtitle'] !== null) {
            $p->setSubtitle($data['subtitle']);
        }
        if (array_key_exists('advantageOne', $data) && $data['advantageOne'] !== null) {
            $p->setAdvantageOne($data['advantageOne']);
        }
        if (array_key_exists('advantageTwoo', $data) && $data['advantageTwoo'] !== null) {
            $p->setAdvantageTwoo($data['advantageTwoo']);
        }
        if (array_key_exists('advantageThree', $data) && $data['advantageThree'] !== null) {
            $p->setAdvantageThree($data['advantageThree']);
        }
        if (array_key_exists('phone', $data) && $data['phone'] !== null) {
            $p->setPhone($data['phone']);
        }
        if (array_key_exists('adress', $data) && $data['adress'] !== null) {
            $p->setAdress($data['adress']);
        }
        if (array_key_exists('email', $data) && $data['email'] !== null) {
            $p->setEmail($data['email']);
        }

        // companyName может быть nullable — ставим как есть
        if (array_key_exists('companyName', $data)) {
            $p->setCompanyName($data['companyName']);
        }

        if (array_key_exists('mapPosition', $data) && $data['mapPosition'] !== null) {
            $p->setMapPosition($data['mapPosition']);
        }

        // уже сохранённые имена файлов из сессии
        if (!empty($data['bannerImg'])) {
            $p->setBannerImg($data['bannerImg']);
        }
        if (!empty($data['image'])) {
            $p->setImage($data['image']);
        }
    }

    /** Определяем шаг по первой ошибке формы (по имени поля) */
    private function firstInvalidStepFromForm(FormInterface $form): int
    {
        foreach ($form->getErrors(true) as $error) {
            $origin = $error->getOrigin();
            if ($origin instanceof FormInterface) {
                $name = $origin->getName();
                if (isset(self::FIELD_STEP[$name])) {
                    return self::FIELD_STEP[$name];
                }
            }
        }
        return 0;
    }

    /**
     * Обработка загрузок при РЕДАКТИРОВАНИИ (замена файлов).
     */
    private function handleUploads(FormInterface $form, UserPage $page): void
    {
        $uploadDir = $this->getParameter('uploads_directory');

        foreach (['bannerImg' => 'setBannerImg', 'image' => 'setImage'] as $field => $setter) {
            $file = $form->get($field)->getData();
            if ($file) {
                $newName = uniqid('', true) . '.' . $file->guessExtension();
                try {
                    $file->move($uploadDir, $newName);
                    $page->$setter($newName);
                } catch (FileException $e) {
                    $this->addFlash('error', "Ошибка при загрузке файла «{$field}».");
                }
            }
        }
    }
}
