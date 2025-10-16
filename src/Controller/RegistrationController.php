<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/create-account', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());
            $user->setRoles(['ROLE_USER']);

            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $userPasswordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            // ======= Отправка письма подтверждения регистрации =======
            $logFile = '/var/www/html/var/log/mailer_debug.log';
            file_put_contents($logFile, "=== NEW REGISTRATION ===\n", FILE_APPEND);
            file_put_contents($logFile, "User: {$user->getEmail()}\n", FILE_APPEND);

            try {
                $email = (new Email())
                    ->from('contact@expfr.fr')
                    ->to($user->getEmail())
                    ->subject('Добро пожаловать на ExpFr.fr 🎉')
                    ->text("Привет, {$user->getEmail()}!\n\nВаш аккаунт успешно создан.\n\nСпасибо за регистрацию на ExpFr.fr!")
                    ->html("
                        <h2 style='color:#0f172a;margin-bottom:10px;'>Добро пожаловать, {$user->getEmail()}!</h2>
                        <p style='color:#334155;'>Ваш аккаунт успешно создан на платформе <strong>ExpFr.fr</strong>.</p>
                        <p style='color:#64748b;margin-top:15px;'>Спасибо за регистрацию!</p>
                    ");

                $mailer->send($email);
                file_put_contents($logFile, "✅ Email sent successfully to {$user->getEmail()}\n\n", FILE_APPEND);
            } catch (\Throwable $e) {
                file_put_contents($logFile, "❌ MAIL SEND ERROR: {$e->getMessage()}\n", FILE_APPEND);
                file_put_contents($logFile, $e->getTraceAsString() . "\n", FILE_APPEND);
            }
            // =========================================================

            // Редирект после успешной регистрации
            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
