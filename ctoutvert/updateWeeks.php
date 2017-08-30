<?php

// Ce fichier doit être lancé une fois par semaine. Il permet de supprimer les produits dont le week-end est passé.
// Idéalement, ce fichier devrait être lancé, le dimanche matin.
class Connexion
{
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

    public function updateProductEspaceClient()
    {
        $pdo = new Connexion();
        $pdoEc = $this->connexionBddEspaceClient();

        $stmt = $pdoEc->prepare("SELECT date_week FROM product");
        $stmt->execute();
        $dateProducts = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $todaysDate = (new \DateTime());
        $dateToString = $todaysDate->format('Y-m-d');

        //boucle pour récupérer les dates. De cette manière quand un produit a été remisé mais que le week-end de remise est dépassé
        foreach ($dateProducts as $dateProduct) {
            //passe à 0 les produits dont la date est expiré
            $stmt = $pdoEc->prepare("UPDATE product SET product_visibility = 0 WHERE date_week <= '$dateToString'");
            $stmt->execute();
        }
    }
    
    function connexionBddCamplive(){

        $hote = '';
        $db = '';
        $login = '';
        $mdp = '';

        try {
            $pdoCl = new PDO('mysql:host='.$hote.';dbname='.$db, $login, $mdp, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
        } catch (Exception $e) {
            die('Erreur : ' . $e->getMessage());
        }
        return $pdoCl;
    }

     public function deleteProductCampLive()
    {
        $pdo = new Connexion();
        $pdoCampLive = $this->connexionBddCamplive();

        $stmt = $pdoCampLive->prepare("SELECT date_week FROM PADestockage");
        $stmt->execute();
        $dateProducts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $todaysDate = (new \DateTime());
        $dateToString = $todaysDate->format('Y-m-d');
        
        //boucle pour récupérer les dates. De cette manière quand un produit a été remisé mais que le week-end de remise est dépassé
        foreach ($dateProducts as $dateProduct) {
            //passe à 0 les produits dont la date est expiré
            $stmt = $pdoCampLive->prepare("DELETE FROM PADestockage WHERE date_week <= '$dateToString'");
            $stmt->execute();
        }
    }
}

$product = new Connexion();
$product->updateProductEspaceClient();
$product->deleteProductCampLive();
echo 'ok';
