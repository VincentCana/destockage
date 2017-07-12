<?php

namespace DiscountBundle\Services;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use DiscountBundle\Entity\ShowProduct;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use \DateTime;

class ProductDiscountService
{
    public function __construct(EntityManager $em, TokenStorage $tokenStorage)
    {
        //Son constructeur avec l'entity manager en paramètre
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }


    public function parseXML($campLiveId)
    {
        //fichier récupérant le xml et fonction de vidage à chaque chargement de page
        $filepath ="../web/dispo.xml";
        unlink($filepath);
        $file = file_get_contents('http://espaceclient.francecom.fr/destockage/subfolder2.xml');

        //réécriture du fichier avec une balise englobante pour pouvoir le parser
        $file2 = "<discount>".$file."</discount>";

        //enregistrement du fichier
        file_put_contents($filepath, $file2, FILE_APPEND);

        //instanciation de la classe DomDocument afin de parser le XML
        $dom = new \DomDocument();
        $dom->load($filepath);
        $productList = $dom->getElementsByTagName('state_product_type');

        foreach ($productList as $list) {
            if (($list->attributes->getNamedItem("id_company")->nodeValue) == $campLiveId) {
                //instanciation de ShowProduct et setting des données afin de persister en BDD
                $showProduct = new ShowProduct();
                $idCompany = $list->attributes->getNamedItem("id_company")->nodeValue;
                $idProducts = $list->attributes->getNamedItem("id_product_type")->nodeValue;
                $wording = $list->attributes->getNamedItem("wording")->nodeValue;
                $showProduct->getShowIdProduct();
                $showProduct->setShowIdProduct($idProducts);
                $showProduct->getShowWording();
                $showProduct->setShowWording($wording);
                $prices = $list->attributes->getNamedItem("price")->nodeValue;
                $showProduct->getShowCurrentPrice();
                $showProduct->setShowCurrentPrice(floatval($prices));
                $dateWeek = $list->attributes->getNamedItem("arrival_date")->nodeValue;
                $dateWeek =  new DateTime($dateWeek);
                $showProduct->getShowDateWeek();
                $showProduct->setShowDateWeek($dateWeek);
                $date = $dateWeek->format('d-m-Y');
                $showConcatenation = $idCompany . $idProducts . $date;
                $showProduct->setShowConcatenation($showConcatenation);
                $showProduct->getShowCampLiveId();
                $showProduct->setShowCampLiveId($idCompany);
                $this->em->persist($showProduct);
                $this->em->flush($showProduct);
            }
        }
    }
}
