<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Psr\Log\LoggerInterface;

class LogInController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, LoggerInterface $logger): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($error) {
            $logger->error('Ошибка входа: ' . $error->getMessageKey());
        }

        $lastEmail = $authenticationUtils->getLastUsername();
    

        return $this->render('login/login.html.twig', [
            'last_email' => $lastEmail,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Этот метод перехватывается системой безопасности.');
    }
}