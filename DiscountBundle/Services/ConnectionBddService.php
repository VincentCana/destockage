<?php

namespace DiscountBundle\Services;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use DiscountBundle\Entity\ShowProduct;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use \PDO;

class ConnectionBddService
{
    public function connectionBdd()
    {
        $hote = '****';
        $db = '****';
        $login = '****';
        $mdp = '****';

        try {
            $pdo = new PDO('mysql:host='.$hote.';dbname='.$db, $login, $mdp, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
        } catch (Exception $e) {
            die('Erreur : ' . $e->getMessage());
        }
        return $pdo;
    }
}
