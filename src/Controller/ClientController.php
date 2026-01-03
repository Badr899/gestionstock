<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;


#[Route('/client')]
#[IsGranted('ROLE_CLIENT')]


final class ClientController extends AbstractController
{
    #[Route('/', name: 'app_client')]
    public function index(): Response
    {
        return $this->render('client/index.html.twig', [
            'controller_name' => 'ClientController',
        ]);
    }

     #[Route('/client/produits', name: 'client_produits')]
    public function produits(ProduitRepository $produitRepository): Response
    {
        return $this->render('client/produit.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }



    // pour le bouton acheter il va cree une session

    #[Route('/client/panier/add', name: 'client_panier_add', methods: ['POST'])]
public function addPanier( Request $request, ProduitRepository $produitRepository, EntityManagerInterface $em): Response {
    $qty = $request->request->get('quantite');
    $id = $request->request->get('produit_id');


    $produit = $produitRepository->find($id);

    if (!$produit) {
        throw $this->createNotFoundException('Produit introuvable');
    }

// ✅ Vérification du stock
    if ($produit->getStock() < $qty) {
        $this->addFlash('error', 'Stock insuffisant');
        return $this->redirectToRoute('client_produits');
    }

    // ✅ Diminution du stock
    $produit->setStock($produit->getStock() - $qty);

    // Sauvegarde en base
    $em->flush();

    // ✅ Gestion du panier (session)
    $session = $request->getSession();
    $panier = $session->get('panier', []);

    $panier[$id] = ($panier[$id] ?? 0) + $qty;

    $session->set('panier', $panier);

    return $this->redirectToRoute('client_produits');
}

// pour afficher le panier

#[Route('/client/panier', name: 'client_panier')]
public function afficherPanier(Request $request, ProduitRepository $produitRepository): Response
 {
    // Récupérer le panier depuis la session
    $panier = $request->getSession()->get('panier', []);

    $produits = [];
    $total = 0;

    foreach ($panier as $id => $qty) {
        $produit = $produitRepository->find($id);
        if ($produit) {
            $produits[] = [
                'produit' => $produit,
                'quantite' => $qty,
                'total' => $produit->getPrix() * $qty
            ];
            $total += $produit->getPrix() * $qty;
        }
    }

    return $this->render('client/panier.html.twig', [
        'items' => $produits, //contient les produits avec quantités et total par produit.
        'total' => $total  //contient la somme totale du panier. npour utiliser dns twig
    ]);
}


//pour supprimer un panier



#[Route('/client/panier/remove', name: 'client_panier_remove', methods: ['POST'])]
public function removeFromPanier(Request $request,  ProduitRepository $produitRepository,EntityManagerInterface $em): Response {
    $id = $request->request->get('produit_id');

    $session = $request->getSession();
    $panier = $session->get('panier', []);

    if (!isset($panier[$id])) {
        return $this->redirectToRoute('client_panier');
    }

    $quantite = $panier[$id];

    $produit = $produitRepository->find($id);
    if ($produit) {
        // ✅ Rendre le stock au fournisseur
        $produit->setStock($produit->getStock() + $quantite);
        $em->flush();
    }

    // ✅ Supprimer du panier
    unset($panier[$id]);
    $session->set('panier', $panier);

    return $this->redirectToRoute('client_panier');
}










}
