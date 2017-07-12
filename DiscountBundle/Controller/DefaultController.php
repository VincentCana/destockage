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
use DiscountBundle\Services\ProductService;

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
     * permet de récupérer le XML des dispos, de le parser et de persister les données en BDD
     * @Route("/discount",name="discount_view")
     * @Method({"GET", "POST"})
     */
    public function indexAction()
    {

        //création des variables, du samedi au samedi et sur 3 semaines, pour l'affichage sur la page d'accueil
        $year = date("Y");
        $week = $this->countWeekAction();

        $dateSaturday1 = $year. "-" .$this->getNextSaturday($week, $year, $this->getMonday($week, $year), 5);
        $dateSaturday2 = $year. "-" .$this->getNextSaturday($week, $year, $this->getMonday($week, $year), 12);
        $dateSaturday3 = $year. "-" .$this->getNextSaturday($week, $year, $this->getMonday($week, $year), 19);

        $weekTitle1 = $this->getSaturday($week, $year, $this->getMonday($week, $year), 5, 12);
        $weekTitle2 = $this->getSaturday($week, $year, $this->getMonday($week, $year), 12, 19);
        $weekTitle3 = $this->getSaturday($week, $year, $this->getMonday($week, $year), 19, 26);

        $weeks1 = [$dateSaturday1 => $weekTitle1];
        $weeks2 = [$dateSaturday2 => $weekTitle2];
        $weeks3 = [$dateSaturday3 => $weekTitle3];

        //récupération de l'id de l'utilisateur
        $campLiveId = $this->get("security.token_storage")->getToken()->getUser()->getCampLiveId();
        $em = $this->getDoctrine()->getManager();
        $tickets = $em->getRepository('TicketBundle:Ticket')->findTicketsBydate5();

        // a chaque chargement de la page, supprime les produits de l'utilisateur
        //pour éviter les conflits lors de la saisie d'une promo en cas de connexion multiple
        $em->getRepository('DiscountBundle:ShowProduct')->deleteProductByCamping($campLiveId);

        //appel le service dans ProductDiscountService et la fonction parseXML()
        $this->get('discount.parsexml')->parseXML($campLiveId);

        //récupération de tous les showProducts qui n'ont pas déjà été destockés pour l'affichage sur la page d'accueil
        $showProducts = $em->getRepository('DiscountBundle:ShowProduct')->findProductsAlreadyExists($campLiveId);

        //récupération de tous les produits destockés pour l'affichage sur la page d'accueil
        $products = $em->getRepository('DiscountBundle:Product')->productVisibility($campLiveId);

        $sumProductVisibility = $em->getRepository('DiscountBundle:Product')->sumProductVisibility($campLiveId);
        $sumProductVisibility = $sumProductVisibility[1];

        return $this->render('DiscountBundle:Default:index.html.twig', [
                'showProducts' => $showProducts,
                'products'=> $products,
                'tickets'=> $tickets,
                'sumProductVisibility' => $sumProductVisibility,
                'weeks1' => $weeks1,
                'weeks2' => $weeks2,
                'weeks3' => $weeks3
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

        //récupération des données d'un showProduct qui resteront inchangées pour l'objet Product
        $showProduct = $em->getRepository('DiscountBundle:ShowProduct')->find($id);

        $showIdProduct = $showProduct->getShowIdProduct();
        $showDateWeek = $showProduct->getShowDateWeek();
        $showCurrentPrice = $showProduct->getShowCurrentPrice();
        $showCampLiveId = $showProduct->getShowCampLiveId();
        $showCurrentPrice = floatval(str_replace(',', '.', $showCurrentPrice));

        //instanciation de Product pour créer un nouveau product
        $product = new Product();

        //récupération de l'id de l'utilisateur
        $campLiveId = $this->get("security.token_storage")->getToken()->getUser()->getCampLiveId();

        //setting des données idem au showProduct
        $product->setCurrentPrice(floatval($showCurrentPrice));
        $product->setIdProduct($showIdProduct);
        $product->setCampLiveId($showCampLiveId);
        $product->setDateWeek($showDateWeek);

        //création d'un attribut pour comparaison avec showProduct (gestion de l'affichage)
        $date = $showDateWeek->format('d-m-Y');
        $showConcatenation = $showCampLiveId . $showIdProduct . $date;
        $product->setConcatenation($showConcatenation);
        $product->setKeyProduct(md5($showConcatenation));


        //on va chercher les produits avec la même concaténation que le produit crée et avec une visibilité de 1
        $productConcat = $em->getRepository('DiscountBundle:Product')->productConcactAlreadyExists($showConcatenation);

        //lors de la création d'un produit la visibilité est à true par défaut
        $product->setProductVisibility(true);

        $sumProductVisibility = $em->getRepository('DiscountBundle:Product')->sumProductVisibility($showCampLiveId);
        $sumProductVisibility = $sumProductVisibility[1];

        //création du formulaire
        $formNewProduct = $this->createForm(ProductDiscountType::class, $product);
        $formNewProduct->handleRequest($request);

        if ($formNewProduct->isSubmitted() && $formNewProduct->isValid()) {

            //on vérifie qu'il n'y a moins de 3 produits dont la visibilité est à 1
            //ainsi que le fait qu'un produit dont la visibilité est à 1 avec la même concaténation n'existe pas.
            // $campLiveId == $showCampLiveId permet de vérifier que la personne qui ajoute un produit est du meme camping
            if ($sumProductVisibility < 3 && $productConcat == null && $campLiveId == $showCampLiveId) {

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
                //appel la fonction d'insert vers PAWEB
                $this->insertProductInPaWeb($product);
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

    //permet de persiste les données sur le serveur PAWEB
    public function insertProductInPaWeb($product)
    {
        $pdo = $this->get('discount.connectionBdd')->connectionBdd();
        $title = $product->getTitle();
        $price= $product->getNewPrice();
        $keyProduct = $product->getKeyProduct();
        $campLiveId = $product->getCampLiveId();
        $idProduct = $product->getIdProduct();
        $date = $product->getDateWeek();
        $dateWeek = $date->format('Y-m-d 00:00:00');

        $stmt = $pdo->prepare("INSERT INTO PADestockage(key_product, camp_live_id, title, id_product, price, date_week)
          VALUES('$keyProduct', '$campLiveId', '$title', '$idProduct', '$price', '$dateWeek') ");

        $stmt->execute();
    }

    //permet de mettre à jour les donners sur le serveur PAWEB
    public function updateProductInPaWeb($product)
    {
        $pdo = $this->get('discount.connectionBdd')->connectionBdd();
        $title = $product->getTitle();
        $price= $product->getNewPrice();
        $keyProduct = $product->getKeyProduct();

        $stmt = $pdo->prepare("UPDATE PADestockage SET title = '$title', price = '$price' WHERE key_product = '$keyProduct'");
        $stmt->execute();
    }

    /**
     * Modification d'un produit destocké déjà existant
     * @Route("/updateProduct/{id}",name="update_view")
     * @Method({"GET", "POST"})
     */
     public function updateProductAction(Request $request, $id)
     {
         //récupérationde l'id du camping qui est connecté
         $campLiveIdConnect = $this->get("security.token_storage")->getToken()->getUser()->getCampLiveId();

         //récupération des données relatives au produit
         $em = $this->getDoctrine()->getManager();
         $updateProduct = $em->getRepository('DiscountBundle:Product')->findOneById($id);

         //récupération de l'id du camping en base de donnée
         $campLiveIdData = $updateProduct->getCampLiveId();

         //création du formulaire pré-rempli
         $formUpdateProduct = $this->createForm(ProductUpdateType::class, $updateProduct);
         $formUpdateProduct->handleRequest($request);

         if ($formUpdateProduct->isSubmitted() && $formUpdateProduct->isValid()) {
             if ($campLiveIdConnect == $campLiveIdData) {
                 $updateCurrentPrice = floatval($updateProduct->getCurrentPrice());
                 $updateProduct->setCurrentPrice(floatval($updateCurrentPrice));
                 $updateDiscountValue = floatval($request->request->get('product_update')['discountValue']);
                 $updateProduct->setDiscountValue(floatval($updateDiscountValue));
                 $discountType = $updateProduct->getDiscountType();
                 $title = $updateProduct->getTitle();
                 $priority = $updateProduct->getPriority();

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
                $p = $em->getRepository('DiscountBundle:Product')->updatePicture($id, $title, $discountType, $updateDiscountValue, $newPrice, $priority);
                $em->flush($p);
            }
                 //appel la fonction d'update vers PAWEB
                 $this->updateProductInPaWeb($updateProduct);
                 return new JsonResponse();
             }
         }

         return $this->render('DiscountBundle:Default:updateProduct.html.twig', array(
                'idClient' => $id,
                'updateProduct' => $updateProduct,
                'formUpdate' => $formUpdateProduct->createView()
            ));
     }

    // permet de supprimer un produit de PAWEB
    public function deleteProductInPaWeb($product)
    {
        $pdo = $this->get('discount.connectionBdd')->connectionBdd();
        $keyProduct = $product->getKeyProduct();

        $stmt = $pdo->prepare("DELETE FROM PADestockage WHERE key_product = '$keyProduct'");
        $stmt->execute();
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
             // appel la fonction de suppression vers PAWEB
             $this->deleteProductInPaWeb($deleteProduct);
             $em->flush($deleteProduct);
         }
         return $this->redirectToRoute('discount_view');
     }
}
