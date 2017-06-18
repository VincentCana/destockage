<?php

namespace src\DiscountBundle\Services;

use src\DiscountBundle\Entity\Product;

class ConnectionService{


    public function generateProduct(Product $product)
    {
        $product = \Product::newInstance()
                            ->setTitle('Mobilhome1')
                            ->setPicture()
                            ->setCurrentPrice('500,50')
                            ->setNewPrice('400,00')
    }
}