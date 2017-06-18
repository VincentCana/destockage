<?php

namespace DiscountBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use DiscountBundle\Entity\Product;
use DiscountBundle\Entity\ShowProduct;
use DiscountBundle\Form\ProductDiscountType;
use DiscountBundle\Form\ProductUpdateType;
use DiscountBundle\Form\ShowProductType;
use \DateTime;
use \PDO;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Controller\Annotations as Rest;

class DefaultController extends Controller
{
    /*
      * Transfère à la fonction d'affichage par semaine
      * la date actuelle, plus précisément le numéro de semaine
      */
     public function countWeekAction()
     {
         $week = (new \DateTime())->format("W");
         return $week;
     }
    /*
     * Permet de récupérer le lundi de la semaine
     */
    public function getMonday($week, $year)
    {
        $timestamp = mktime(0, 0, 0, 1, 1, $year) + ($week * 7 * 24 * 60 * 60);
        $timestamp_for_monday = $timestamp - 86400 * (date('N', $timestamp) - 1);
        $monday = date('Y-m-d', $timestamp_for_monday);
        return $monday;
    }
    /*
     * Permet de récupérer le samedi à venir. Pour ça, on part du lundi et on compte +5
     * Ensuite on rajoute +7 pour passer au samedi d'après.
     * Les fonctions getSaturday2 et getSaturday3 font la même chose mais pour les semaines suivantes.
     * De cette manière, on peut se projeter sur les 3 prochaines semaines, à partir du samedi à venir
     */
    public function getSaturday($week, $year, $monday, $s1, $s2)
    {
        $saturday1 = date('d/m', strtotime($s1 . ' day', strtotime($monday)));
        $saturday2 = date('d/m', strtotime($s2 . ' day', strtotime($monday)));
        $weekTitle1 = 'Du ' . $saturday1 . ' au ' . $saturday2;
        return $weekTitle1;
    }
    /*
    * Permet de récupérer le samedi à venir. Pour ça, on part du lundi et on compte +5
    */
    public function getNextSaturday($week, $year, $monday, $count)
    {
        $saturday = date('m-d', strtotime($count . ' day', strtotime($monday)));
        return $saturday;
    }

    /**
     * @Route("/discount",name="discount_view")
     * permet de récupérer le XML des dispos, de le parser et de persiter les données en BDD
     * @Method({"GET", "POST"})
     */
    public function indexAction()
    {
        //connexion à la BDD pour vider la table show_product à chaque rechargement de page
        try {
            $bdd = new PDO('mysql:host=localhost; dbname=espaceclient; charset=utf8', 'root', '');
        } catch (Exception $e) {
            die('Erreur : ' . $e->getMessage());
        }
        $truncateTable = $bdd->query('TRUNCATE TABLE show_product');

        //création des variables, du samedi au samedi et sur 3 semaines, pour l'affichage sur la page d'accueil
        $year = date("Y");
        $week = $this->countWeekAction();

        $dateSaturday1 = $year. "-" .$this->getNextSaturday($week, $year, $this->getMonday($week, $year), 5);
        $dateSaturday2 = $year. "-" .$this->getNextSaturday($week, $year, $this->getMonday($week, $year), 12);
        $dateSaturday3 = $year. "-" .$this->getNextSaturday($week, $year, $this->getMonday($week, $year), 19);

        $weekTitle1 = $this->getSaturday($week, $year, $this->getMonday($week, $year), 5, 12);
        $weekTitle2 = $this->getSaturday($week, $year, $this->getMonday($week, $year), 12, 19);
        $weekTitle3 = $this->getSaturday($week, $year, $this->getMonday($week, $year), 19, 26);

        $weeks=[$dateSaturday1 => $weekTitle1, $dateSaturday2 => $weekTitle2, $dateSaturday3 => $weekTitle3 ];


        //récupération de l'id de l'utilisateur
        $campLiveId = $this->get("security.token_storage")->getToken()->getUser()->getCampLiveId();

        //fichier récupérant le xml et fonction de vidage à chaque chargement de page
        $filepath ="../web/dispo.xml";
        unlink($filepath);

        //utilisation de Curl pour se connecter au serveur distant Camplive
        //et aller chercher le fichier XML des disponibilités
        $lien = 'http://camplive.com/admin/destockage/subfolder2.xml';
        $user= 'camplive';
        $pass = 'pa98mico';

        $curl=curl_init();
        curl_setopt($curl, CURLOPT_URL, $lien);
        curl_setopt($curl, CURLOPT_USERPWD, "$user:$pass");
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $file = curl_exec($curl);

        //réécrture du fichier avec une balise englobante pour pouvoir le parser
        $file2 = "<discount>".$file."</discount>";
        //enregistrement du fichier
        file_put_contents($filepath, $file2, FILE_APPEND);
        curl_close($curl);


        //instanciation de la casse DomDocument afin de parser le XML
        $dom = new \DomDocument();
        $dom->load($filepath);
        $productList = $dom->getElementsByTagName('state_product_type');

        foreach ($productList as $list) {
            if (($list->attributes->getNamedItem("id_company")->nodeValue) == $campLiveId) {

                //instanciation de ShowProduct et setting des données afin de persister en BDD
                $showProduct = new ShowProduct();
                $idCompany = $list->attributes->getNamedItem("id_company")->nodeValue;
                $idProducts = $list->attributes->getNamedItem("id_product_type")->nodeValue;
                $showProduct->getShowIdProduct();
                $showProduct->setShowIdProduct($idProducts);
                $prices = $list->attributes->getNamedItem("prix")->nodeValue;
                $showProduct->getShowCurrentPrice();
                $showProduct->setShowCurrentPrice(floatval($prices));
                $dateWeek = $list->attributes->getNamedItem("arrival_date")->nodeValue;
                $dateWeek =  new DateTime($dateWeek);
                $showProduct->getShowDateWeek();
                $showProduct->setShowDateWeek($dateWeek);
                $date = $dateWeek->format('d-m-Y');
                $showConcatenation = $idCompany . $idProducts . $date;
                $showProduct->setShowConcatenation($showConcatenation);
                $em = $this->getDoctrine()->getManager();
                $em->persist($showProduct);
                $em->flush($showProduct);
            }
        }

        $em = $this->getDoctrine()->getManager();
        //récupération de tous les showProducts qui n'ont pas déjà été destockés pour l'affichage sur la page d'accueil
        $showProducts = $em->getRepository('DiscountBundle:ShowProduct')->findProductsAlreadyExists();

        $tickets = $em->getRepository('TicketBundle:Ticket')->findTicketsBydate5();

        //récupération de tous les produits destockés pour l'affichage sur la page d'accueil
        $products = $em->getRepository('DiscountBundle:Product')->productVisibility($campLiveId);

        $todaysDate = (new \DateTime());
        //boucle pour récupérer les dates. De cette manière quand un produit a été remisé mais que le week-end de remise est dépassé
        //on passe la visibility à false
        foreach ($products as $product) {
            $dateWeek = $product->getDateWeek();

            //condition qui compare date du jour avec la date du week-end
            if ($dateWeek <= $todaysDate) {
                $product->setProductVisibility(false);
                $em->flush($product);
            }
        }
        $sumProductVisibility = $em->getRepository('DiscountBundle:Product')->sumProductVisibility($campLiveId);
        $sumProductVisibility = $sumProductVisibility[1];

        return $this->render('DiscountBundle:Default:index.html.twig', [
                'showProducts' => $showProducts,
                'weeks' => $weeks,
                'products'=>$products,
                'tickets'=>$tickets,
                'sumProductVisibility' => $sumProductVisibility
            ]);
    }




    /**
     * Création d'un produit destocké
     * @Route("/newProduct/{id}",name="new_view")
     * @Method({"GET", "POST"})
     */
    public function newProductAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        //récupération des données d'un showProductqui resteront inchangées pour l'objet Product'
        $showProduct = $em->getRepository('DiscountBundle:ShowProduct')->find($id);
        $showIdProduct = $showProduct->getShowIdProduct();
        $showDateWeek = $showProduct->getShowDateWeek();
        $showCurrentPrice = $showProduct->getShowCurrentPrice();
        $showCurrentPrice = floatval(str_replace(',', '.', $showCurrentPrice));

        //instanciation de Product pour créer un nouveau product
        $product = new Product();

        $campLiveId = $this->get("security.token_storage")->getToken()->getUser()->getCampLiveId();

        //setting des données idem au showProduct
        $product->setCurrentPrice(floatval($showCurrentPrice));
        $product->setIdProduct($showIdProduct);
        $product->setCampLiveId($campLiveId);
        $product->setDateWeek($showDateWeek);

        //création d'un attribut pour comparaison avec showProduct (gestion de l'affichage)
        $date = $showDateWeek->format('d-m-Y');
        $showConcatenation = $campLiveId . $showIdProduct . $date;
        $product->setConcatenation($showConcatenation);

        //on va chercher les produits avec la même concaténation que le produit crée et avec une visibilité de 1
        $productConcat = $em->getRepository('DiscountBundle:Product')->productConcactAlreadyExists($showConcatenation);

        //lors de la création d'un produit la visibilité est à true par défaut
        $product->setProductVisibility(true);


        $sumProductVisibility = $em->getRepository('DiscountBundle:Product')->sumProductVisibility($campLiveId);
        $sumProductVisibility = $sumProductVisibility[1];

        //création du formulaire
        $formNewProduct = $this->createForm(ProductDiscountType::class, $product);
        $formNewProduct->handleRequest($request);

        if ($formNewProduct->isSubmitted() && $formNewProduct->isValid()) {

            //on vérifie qu'il n'y a moins de 3 produits dont la visibilité est à 1
            //ainsi que le fait qu'un produit dont la visibilité est à 1 avec la même concaténation n'existe pas.
            if ($sumProductVisibility < 3 && $productConcat == null) {

                //Calcul du nouveau prix si la remise est en €
                if (($request->request->get('product_discount')['discountType'])== '0') {
                    $result = ($product->getCurrentPrice() - $product->getDiscountValue());
                    $newPrice = floatval(str_replace(',', '.', $result));

                    if ($newPrice <= 0) {
                        throw $this->createNotFoundException("Erreur : la remise ne peut pas être supérieure au prix");
                    } else {
                        $product->setNewPrice($newPrice);
                    }
                }
                //Calcul du nouveau prix si la remise est en %
                if (($request->request->get('product_discount')['discountType'])== '1') {
                    $result = $product->getCurrentPrice() - ($product->getCurrentPrice() * ($product->getDiscountValue()/100));
                    $newPrice = floatval(str_replace(',', '.', $result));
                    if ($newPrice <= 0) {
                        throw $this->createNotFoundException("Erreur : la remise ne peut pas être supérieure au prix en pourcentage");
                    } else {
                        $product->setNewPrice($newPrice);
                    }
                }

                //si le formulaire contient une image
                    if ($request->files->get('product_discount')['picture'] !== null) {

                    //upload et persistance de l'image
                    $picture = $product->getPicture();
                    //permet de récupérer le nom de l'image
                    $pictureName = $picture->getClientOriginalName();
                    //hash le nom de l'image
                    $pictureNameHash = md5(uniqid()).'.'.$picture->guessExtension();
                        $picture->move(
                        $this->getParameter('files_directory'),
                        $pictureNameHash
                    );
                        $product->setPictureName($pictureName);
                        $product->setPicture($pictureNameHash);
                    }
                $em->persist($product);
                $em->flush();

                return new JsonResponse($product);
            }
        }

        return $this->render('DiscountBundle:Default:newProduct.html.twig', array(
                 'idClient' => $id,
                 'product' => $product,
                 'formNew' => $formNewProduct->createView(),
                 'sumProductVisibility' => $sumProductVisibility
             ));
    }

    /**
     * Modification d'un produit destocké déjà existant
     * @Route("/updateProduct/{id}",name="update_view")
     * @Method({"GET", "POST"})
     */
     public function updateProductAction(Request $request, $id)
     {
         //récupérationde l'id du camping
         $campLiveId = $this->get("security.token_storage")->getToken()->getUser()->getCampLiveId();

         //récupération des données relatives au produit
         $em = $this->getDoctrine()->getManager();
         $updateProduct = $em->getRepository('DiscountBundle:Product')->findOneById($id);

         //création du formulaire pré-rempli
         $formUpdateProduct = $this->createForm(ProductUpdateType::class, $updateProduct);
         $formUpdateProduct->handleRequest($request);

         if ($formUpdateProduct->isSubmitted() && $formUpdateProduct->isValid()) {
             $updateCurrentPrice = floatval($updateProduct->getCurrentPrice());
             $updateProduct->setCurrentPrice(floatval($updateCurrentPrice));
             $updateDiscountValue = floatval($request->request->get('product_update')['discountValue']);
             $updateProduct->setDiscountValue(floatval($updateDiscountValue));
             $discountType = $updateProduct->getDiscountType();
             $title = $updateProduct->getTitle();

            //Calcul du nouveau prix si la remise est en €
            if (($request->request->get('product_update')['discountType'])== '0') {
                $result = ($updateProduct->getCurrentPrice() - $updateProduct->getDiscountValue());
                $newPrice = floatval(str_replace(',', '.', $result));
                if ($newPrice <= 0) {
                    throw $this->createNotFoundException("Erreur : la remise ne peut pas être supérieure au prix");
                } else {
                    $updateProduct->setNewPrice($newPrice);
                }
            }
            //Calcul du nouveau prix si la remise est en %
            if (($request->request->get('product_update')['discountType'])== '1') {
                $result = $updateProduct->getCurrentPrice() - ($updateProduct->getCurrentPrice() * ($updateProduct->getDiscountValue()/100));
                $newPrice = floatval(str_replace(',', '.', $result));
                if ($newPrice <= 0) {
                    throw $this->createNotFoundException("Erreur : la remise ne peut pas être supérieure au prix en pourcentage");
                } else {
                    $updateProduct->setNewPrice($newPrice);
                }
            }

            //si on upload une autre photo
            if ($request->files->get('product_update')['picture'] !== null) {
                $picture = $updateProduct->getPicture();

                //permet de récupérer le nom de l'image
                $pictureName = $picture->getClientOriginalName();
                //hash le nom de l'image
                $pictureNameHash = md5(uniqid()).'.'.$picture->guessExtension();
                $picture->move(
                    $this->getParameter('files_directory'),
                    $pictureNameHash
                );
                $updateProduct->setPictureName($pictureName);
                $updateProduct->setPicture($pictureNameHash);
                $em->flush($updateProduct);
            }
            //si on ne change pas la photo, celle déjà existante reste persistée
            elseif ($request->files->get('product_update')['picture'] == null) {
                $p = $em->getRepository('DiscountBundle:Product')->updatePicture($id, $title, $discountType, $updateDiscountValue, $newPrice);
                $em->flush($p);
            }

             return new JsonResponse();
         }

         return $this->render('DiscountBundle:Default:updateProduct.html.twig', array(
                'idClient' => $id,
                'updateProduct' => $updateProduct,
                'formUpdate' => $formUpdateProduct->createView()
            ));
     }

     /**
      * Suppression d'un produit (affichage seulement car toujours présent en BDD)
      * @Route("/deleteProduct/{id}",name="delete_product_view")
      * @Method({"GET", "POST"})
      */
     public function deleteProductAction(Request $request, $id)
     {
         $em = $this->getDoctrine()->getManager();
         $deleteProduct = $em->getRepository('DiscountBundle:Product')->find($id);

         $campLiveId = $deleteProduct->getCampLiveId();
         $campLiveIdToken = $this->get("security.token_storage")->getToken()->getUser()->getCampLiveId();

         if ($campLiveId == $campLiveIdToken) {
             $deleteProduct->setProductVisibility(false);
             $em->flush($deleteProduct);
         }

         return $this->redirectToRoute('discount_view');
     }
}
