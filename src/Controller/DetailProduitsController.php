<?php

namespace App\Controller;

use App\Entity\Produits;
use App\Repository\CommentaireRepository;
use App\Controller\DetailProduitsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DetailProduitsController extends AbstractController
{
    /**
     * @Route("/detail/produits", name="app_detail_produits")
     */
    public function index( Request $request, Produits $produits , CommentaireRepository $commentaireRepository): Response
    {

        $commentaire=new Commentaire();
         
         $form = $this->createForm(Commentaires2Type::class,  $commentaire);
         $form->handleRequest($request);

        $commentaireparproduit=$commentaireRepository->findBy([

                    'produits'=>$produit
                ]   
        );  
        // dd($commentaireparproduit) ;
         if ($form->isSubmitted() && $form->isValid()) {


            // je dois enregistrer les info du user
            // $this->getUser() me renvoie les infos sur le user 
            // en cours
            // avec setuser je stock l'information dans le
            // commentaire
            $commentaire->setUsers($this->getUser());

            // je dois enregistrer les infos du produit
            // je recupere $produit issue du param converter
            // on a l'ID dans l'URL en mettant l'entité 
            // dans la fonction on recupere le produit correspondant
            // et ensuite on set dans l'entité commentaire
            $commentaire->setProduits($produit);

            $commentaireRepository->save( $commentaire , true);
            return $this->redirectToRoute('app_produits_index', [], Response::HTTP_SEE_OTHER);

         }

        // on veut afficher les commentaires 
        // correspondant aux produit de l'id de l'url.
        // on utilise le repository qui va chercher avec un critere findby
        // selon le produits 
        // $toutlescommenaire=$commentaireRepository->findAll();
      
         //cascade = CascadeType.ALL
        // $produit correspond à l'entité produit de l'identifiant envoyé
        // en parametre
         return $this->renderForm('detailproduits/index.html.twig', [
            'produit' => $produit,
            'les_commentaires' => $commentaireparproduit,
                
         ]);
    }
}
    
