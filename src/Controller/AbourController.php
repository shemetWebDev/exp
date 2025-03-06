<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AbourController extends AbstractController
{
    #[Route('/about', name: 'about_page')]
    public function index(): Response
    {
        return $this->render('abour/index.html.twig');
    }
}
