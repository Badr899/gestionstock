<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


#[Route('/fournisseur')]
#[IsGranted('ROLE_FOURNISSEUR')]
final class FournisseurController extends AbstractController
{
    #[Route('/', name: 'app_fournisseur')]
    public function index(): Response
    {
        return $this->render('fournisseur/index.html.twig', [
            'controller_name' => 'Mr Fournisseur',
        ]);
    }
}
