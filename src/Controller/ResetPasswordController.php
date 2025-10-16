<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            return $this->processSendingPasswordResetEmail($email, $translator);
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator,
        ?string $token = null
    ): Response {
        if ($token) {
            $this->storeTokenInSession($token);
            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->resetPasswordHelper->removeResetRequest($token);
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $this->entityManager->flush();
            $this->cleanSessionAfterReset();
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, TranslatorInterface $translator): RedirectResponse
    {
        $logFile = '/var/www/html/var/log/mailer_debug.log';
        file_put_contents($logFile, "\n=== PASSWORD RESET REQUEST (MANUAL TRANSPORT TEST) ===\n", FILE_APPEND);
        file_put_contents($logFile, "Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        file_put_contents($logFile, "Email input: {$emailFormData}\n", FILE_APPEND);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $emailFormData]);
        if (!$user) {
            file_put_contents($logFile, "⚠️ User not found for email: {$emailFormData}\n", FILE_APPEND);
            return $this->redirectToRoute('app_check_email');
        }

        file_put_contents($logFile, "✅ User found: ID {$user->getId()}, Email: {$user->getEmail()}\n", FILE_APPEND);

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
            file_put_contents($logFile, "Generated reset token: {$resetToken->getToken()}\n", FILE_APPEND);
        } catch (ResetPasswordExceptionInterface $e) {
            file_put_contents($logFile, "❌ Token generation error: {$e->getMessage()}\n", FILE_APPEND);
            return $this->redirectToRoute('app_check_email');
        }

        try {
            // 👇 Создаём транспорт вручную — как в test_mail.php
            $dsn = $_ENV['MAILER_DSN'] ?? 'smtp://contact@expfr.fr:Shemet_21%21@ssl0.ovh.net:587?encryption=tls&verify_peer=false&auth_mode=login';
            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);

            $email = (new Email())
                ->from('contact@expfr.fr')
                ->to($user->getEmail())
                ->subject('ExpFr.fr – Проверка ручного транспорта')
                ->text("Тестовое письмо от ExpFr.fr\n\nЕсли вы видите это сообщение — всё работает ✅\n\nВремя: " . date('Y-m-d H:i:s'));

            file_put_contents($logFile, "About to send test email via manual transport...\n", FILE_APPEND);
            set_time_limit(15);
            $mailer->send($email);
            file_put_contents($logFile, "✅ Manual transport email successfully sent to {$user->getEmail()}\n", FILE_APPEND);
        } catch (\Throwable $e) {
            file_put_contents($logFile, "❌ MAIL SEND ERROR: {$e->getMessage()}\n", FILE_APPEND);
            file_put_contents($logFile, $e->getTraceAsString() . "\n", FILE_APPEND);
        }

        $this->setTokenObjectInSession($resetToken);
        file_put_contents($logFile, "Redirecting to check-email\n=== PASSWORD RESET REQUEST END ===\n\n", FILE_APPEND);
        return $this->redirectToRoute('app_check_email');
    }
}
