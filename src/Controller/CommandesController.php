<?php

namespace App\Controller;

use DateTime;
use Stripe\Stripe;
use App\Entity\Facture;
use App\Entity\Commandes;
use Stripe\PaymentIntent;
use App\Form\CommandesType;
use App\Service\CartService;
 
use Stripe\Checkout\Session;
use App\Service\EmailService;
use App\Repository\FactureRepository;
use App\Repository\ProduitsRepository;
use App\Repository\CommandesRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommandesController extends AbstractController
    {
        /**
         * @Route("/profile/commande/success", name="app_commande_sucess")
         */

        // #[Route('/profile/commande/success', name: 'app_commande_sucess')]
        public function sucess(
            FactureRepository $factureRepository,
            ProduitsRepository $produitsRepository,
            CommandesRepository $commandesRepository,
            CartService $cartService,
            SessionInterface $session,
            EmailService $emailService
        ):Response
        {


            
            // 1. On va stocké une ligne dans la table facture
            // on créé un objet facture issue de l'entité facture
            $facture=new Facture();
            // on va lui affecté un user en lui mettant l'user en cours
            $facture->setUser($this->getUser());
            // on va lui affecté la propriété correspondant à la date en cours
            // avec un datatime
            $facture->setDatecrea(new DateTime());



            // on utilise le repo de la facture pour enregistrer
            // les repository des entity de servent qu'a lire (méthode find)
            // il y a une personnalisation du repo qui appelle l'entity manager
            // c'est la classe d'écriture de symfony
            $factureRepository->add($facture, true);




            // 2. on va stocké le panier dans la table commande
            /*    $panier=$session->getSession()->get("panier");
            // boucler sur chaque ligne du panier
            foreach ($panier as $key => $value  ){ 
                
                $commande = new Commande();
                $commande->setUsers($this->getUser());
                $commande->setProduits($produitRepository->find($key));
                $commande->setQuantite($value);
                $commandeRepository->save($commande, true);
            }*/
            $panier=$session->get("panier");

            foreach($panier as $key => $value)
            {

                // création d'un objet commande
                $commandes=new Commandes();
                // affectation de la propriété quantité issue du tableau panier
                $commandes->setQuantite($value);
                // affectation de la propriété produit
                // grace au repo du produit
                $commandes->setProduits($produitsRepository->find($key));
                // affectation de la propriété facture issue du 
                // de la facture créé au dessus
                $commandes->setFacture($facture);

                $commandes->setAdresse($session->get('adresse', ''));
                $commandesRepository->add($commandes);
                $factureRepository->add($facture, true);
            }
            $emailService->envoyer(
                "admin@gmail.com",
                $session->get('adresse', ''));
                 

            

            // on vide le panier
            $cartService->clear();
    
            return $this->render(
                "commandes/success.html.twig"
            );
        } 

        /**
         * @Route("/profile/commande/cancel", name="app_commande_cancel")
         */

        // #[Route('/profile/commande/cancel', name: 'app_commande_cancel')]
        public function cancel(){
            dd('le paiement est KO ! ');
        } 
        /**
         * @Route("/profile/commande", name="app_commande")
         */

        // #[Route('/profile/commande', name: 'app_commande')]
        public function index(  RequestStack $session, 
            ProduitsRepository $produitRepository,
            CartService $cart,
            FactureRepository $factureRepository,
            CommandesRepository $commandeRepository,
            Request $request
            ): Response
        {
            // stocké le user
            // this->getUser()
            // stocké le produit
            // via le param converter
            // et la quantité via la session
    
            // stocké le user
            // this->getUser()

            // recuperation du panier
            /*    $panier=$session->getSession()->get("panier");
            // boucler sur chaque ligne du panier
            foreach ($panier as $key => $value  ){ 
                
                $commande = new Commande();
                $commande->setUsers($this->getUser());
                $commande->setProduits($produitRepository->find($key));
                $commande->setQuantite($value);
                $commandeRepository->save($commande, true);
            }*/

            
            

            

            //1. Payer sur STRIPE
            // communiquer avec stripe

            // on a le montant du panier
            $montant=$cart->getTotalAll()*100;


            

            // clé secrete pour que stripe me reconnaisse
            $stripeSecretKey='sk_test_51Mxp86D1lcGGsZVJ1NdbIpaFh7oqyXlameCWrmbJQ63lbpdWeLKDYqoUIWdl315jZNWCrLRb4WND4oeNvqrwbpzu00W1yZ7yGk';
            Stripe::setApiKey($stripeSecretKey);
    
            // on définit le protocol de connexion http ou https
            // avec les variable global PHP de sorte de pouvoir gérer
            // tout les environnements possible

            if (isset($_SERVER['HTTPS'])){
                $protocol="https://";
            } 
            $protocol="http://";
            // on définit le nom du serveur de connexion 
            // avec les variable global PHP de sorte de pouvoir gérer
            // tout les environnements possible
            $serveur=$_SERVER['SERVER_NAME'];
            $YOUR_DOMAIN=$protocol.$serveur;

            // on lance la communication avec stripe



            $checkout_session = \Stripe\Checkout\Session::create([
                'line_items' => [[
                # Provide the exact Price ID (e.g. pr_1234) of the product you want to sell
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $montant,
                    'product_data' => [
                    'name' => 'Paiement de votre panier'
                    ],
                ],
                'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $YOUR_DOMAIN . '/profile/commande/success',
                'cancel_url' => $YOUR_DOMAIN . '/profile/commande/cancel',

            ]);
    
            
            //2. Savegarde en B.D.

            //3. Supprimer la session
    
            
            return $this->redirect($checkout_session->url);
        }

        
        /**
         * @Route("/commande/adresse", name="app_commande_adresse")
         */
        public function adresse(Request $request, EmailService $emailService, SessionInterface $session){
            $form=$this->createForm(CommandesType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                // creer une variable data qui est un tableau clé valeur
                // contenant les données envoyé en POST

                $data = $form->getData();
                $ln = '<br>'.PHP_EOL;

                $adresse = $data['prenom'] . ' ' . strtoupper($data['nom']) . $ln
                            . $data['adresse'] .$ln
                            . $data['postal'] . ' ' . $data['ville'] .$ln .$ln
                            .$data['telephone'];

                $session->set('adresse', $adresse);

                return $this->redirectToRoute('app_commande');
            }

                return $this->render('contact/traitement.html.twig', [
                    'form' => $form->createView()
                ]);


        }
        
    
    }