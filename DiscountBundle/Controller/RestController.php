<?php

namespace DiscountBundle\Controller;

use DiscountBundle\Entity\Product;
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
    * @Route("/restProducts/{campliveId}", name="app_product_list")
    * @Method({"GET", "POST"})
    */
   public function listAction($campliveId)
   {
       $products = $this->getDoctrine()->getRepository('DiscountBundle:Product')->productVisibilityToArray($campliveId);
       return new JsonResponse($products);
   }
}
