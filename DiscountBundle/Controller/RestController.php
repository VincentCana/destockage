<?php

namespace DiscountBundle\Controller;

use DiscountBundle\Entity\Product;
use DiscountBundle\Entity\ShowProduct;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class RestController extends Controller
{
    /**
    * @Route("/restProducts/{campLiveId}", name="app_product_list")
    * @Method({"GET", "POST"})
    */
   public function listAction($campLiveId)
   {
       $em = $this->getDoctrine()->getManager();
       //pour éviter les conflits lors de la saisie d'une promo en cas de connexion multiple
       $em->getRepository('DiscountBundle:ShowProduct')->deleteProductByCamping($campLiveId);
       $this->get('discount.parsexml')->parseXML($campLiveId);
       $products = $this->getDoctrine()->getRepository('DiscountBundle:Product')->productVisibilityToArray($campLiveId);
       return new JsonResponse($products);
   }
}
