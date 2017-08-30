<?php

// Ce fichier permet de vérifier si un produit qui est en promotion a été loué depuis.
// Devrait être appelé toutes les 30 minutes

class Connexion
{
    //connexion au serveur espaceclient (Francecom05)
    public function connexionBddEspaceClient()
    {
        $hote = '';
        $db = '';
        $login = ''; 
      $mdp = ''; 

    try {
        $pdoEc = new PDO('mysql:host='.$hote.';dbname='.$db, $login, $mdp, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
    } catch (Exception $e) {
        die('Erreur : ' . $e->getMessage());
    }

        return $pdoEc;
    }

    //connexion au serveur camplive.deifi
    public function connexionBddCamplive()
    {
        $hote = '';
        $db = '';
        $login = '';
        $mdp = '';

        try {
            $pdo = new PDO('mysql:host='.$hote.';dbname='.$db, $login, $mdp, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
        } catch (Exception $e) {
            die('Erreur : ' . $e->getMessage());
        }
        return $pdo;
    }


    public function deleteProductRentedInPaWeb()
    {
        $pdo = new Connexion();
        $connexionBddEspaceClient = $pdo->connexionBddEspaceClient();
        $connexionBddCamplive = $pdo->connexionBddCamplive();

        //requete qui permet de voir si un produit est en promotion alors qu'il n'existe plus dans le XML
        $stmt = $connexionBddEspaceClient->prepare("SELECT p.concatenation, sp.show_concatenation, p.key_product
          FROM product p
          LEFT JOIN show_product sp ON p.concatenation = sp.show_concatenation
          WHERE sp.show_concatenation IS NULL");
        $stmt->execute();
        $resultProductRenteds = $stmt->fetchAll();
        //permet de récupérer tous les produits qui ne sont plus à la location alors qu'ils sont en promotion
        foreach ($resultProductRenteds as $resultProductRented) {
            $keyProduct = $resultProductRented['key_product'];
            //passe la visibilité du produit à 0 dans la table product de l'espace client.
            $stmt = $connexionBddEspaceClient->prepare("UPDATE product SET product_visibility = 0 WHERE key_product = '$keyProduct'");
            $stmt->execute();

            //supprime le produit de la table PADestockage du serveur PAWEB
            $stmt = $connexionBddCamplive->prepare("DELETE FROM PADestockage WHERE key_product = '$keyProduct'");
            $stmt->execute();
        }
    }
}

$productRented = new Connexion();
$productRented->deleteProductRentedInPaWeb();
echo "ok";
