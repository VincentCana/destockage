<?php

// Permet de récupérer les produits disponibles pour les 3 prochaines semaines. Ce fichier va parser les ID des campings
// que l'on retrouve dans la table fos_user de l'espace client
// Ce fichier doit être lancé toutes les 30 minutes idéalement.

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
    */
    public function getNextSaturday($week, $year, $monday, $count)
    {
        $saturday = date('m-d', strtotime($count . ' day', strtotime($monday)));
        return $saturday;
    }


    public function getCampLiveId()
    {
        $pdoEc = $this->connexionBddEspaceClient();
        $stmt = $pdoEc->prepare('SELECT camp_live_id FROM `fos_user` WHERE camp_live_id is NOT NULL AND camp_live_id != 0 ');
        $stmt->execute();
        $campLiveIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        return $campLiveIds;
    }


    public function getProducts()
    {
        $pdo = new Connexion();
        $campLiveIds = $pdo->getCampLiveId();
        $campLiveDb = $pdo->connexionBddCamplive();

        $year = date("Y");
        $week = $this->countWeekAction();

        $dateSaturday1 = $year. "-" .$this->getNextSaturday($week, $year, $this->getMonday($week, $year), 5);
        $dateSaturday2 = $year. "-" .$this->getNextSaturday($week, $year, $this->getMonday($week, $year), 12);
        $dateSaturday3 = $year. "-" .$this->getNextSaturday($week, $year, $this->getMonday($week, $year), 19);
        //permet de récupérer les trois prochains samedi
        $weeks = [$dateSaturday1, $dateSaturday2, $dateSaturday3];

        foreach ($campLiveIds as $key => $campLiveId) {
            //permet de récupérer la variable $ctvProductType et $ctvMainTarifs
            $stmt = $campLiveDb->prepare("SELECT ctvProductType,ctvMainTarifs FROM PAParam
                        INNER JOIN Entrep ON Entrep.IdEntr = PAParam.CLId
                        INNER JOIN PAWeb ON PAWeb.CLId = PAParam.CLId
                        WHERE PAParam.CLId = ".$campLiveId." ");
            $stmt->execute();
            $request1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $ctvProductType = $request1[0]['ctvProductType'];
            $ctvMainTarifs = $request1[0]['ctvMainTarifs'];

            //requête pour trouver tous les types de produits d'un camping
            $stmt = $campLiveDb->prepare("SELECT PACritere.Ref AS id_product_type,PACritere.Lib AS product_type
                         FROM PACritere
                         WHERE PACritere.CLId='".$campLiveId."'
                         AND PACritere.Type = 2
                         AND PACritere.IdTCrit = '".$ctvProductType."'
                         ;");
            $stmt->execute();
            $request2 = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            //boucle pour permettre de récupérer les trois prochaines semaines.
            for ($i=0; $i < 3 ; $i++) {
                //création d'un tableau avec les résultats de la requête
            foreach ($request2 as $idProduct) {

              //requête pour trouver le tarif d'une location (sans option)
                $stmt = $campLiveDb->prepare("SELECT PASaisRapD.Tarif,
                            IF( TarifLangLibelle IS NULL , PATarif.Lib, TarifLangLibelle ) AS TaLib
                            , PATarifPer.TVA, PATauxTVA.Taux AS TauxTVA
                            , PATarifPer.Compta
                            , PATarifPer.Prix AS prix, PASaisRapD.CLId, PASaisRapD.Choix
                            FROM PATarif, PATarifPer, PATauxTVA, PASaisRapD
                            LEFT JOIN PATarifsLang ON ( PATarifsLang.CLId = PASaisRapD.CLId
                            AND PATarifsLang.TarifRef = PASaisRapD.Tarif
                            AND PATarifsLang.LngRef = 5)
                            WHERE PASaisRapD.CLId = ".$campLiveId."
                            AND PASaisRapD.CLId = PATarif.CLId
                            AND PASaisRapD.CLId = PATarifPer.CLId
                            AND PASaisRapD.CLId = PATauxTVA.CLId
                            AND PASaisRapD.Ref = '".$ctvMainTarifs."'
                            AND PASaisRapD.Choix = '".$idProduct."'
                            AND PASaisRapD.Tarif = PATarif.Ref
                            AND PASaisRapD.Tarif = PATarifPer.Tarif
                            AND PATarifPer.Debut <= '".$weeks[$i]."'
                            AND PATarifPer.Fin >= '".$weeks[$i]."'
                            AND PATarifPer.Ord = 1
                            AND PATauxTVA.Ref = PATarifPer.TVA
                            AND PATauxTVA.Debut <= '".$weeks[$i]."'
                            AND PATauxTVA.Fin >= '".$weeks[$i]."'
                            ;");
                $stmt->execute();

                $request3 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $dataForCheckAvailability[] = [
                    $request3[0]['TaLib'],
                    $request3[0]['CLId'],
                    $weeks[$i],
                    $ctvProductType,
                    $request3[0]['Choix'],
                    $request3[0]['prix']
                ];
            }
            }
        }
        return $dataForCheckAvailability;
    }


    public function checkAvailability($dataForCheckAvailability)
    {
        foreach ($dataForCheckAvailability as $data) {
            $talib = $data[0];
            $campLiveId = $data[1];
            $arrival_date = $data[2];
            $ctvProductType = $data[3];
            $id_product_type = $data[4];
            $price = $data[5];

            $pdo = new Connexion();
            $campLiveDb = $pdo->connexionBddCamplive();

            $filepath = __DIR__ ."/subfolder2.xml";


            $stmt = $campLiveDb->prepare("SELECT Empl
                FROM PAEmplCrit
                WHERE PAEmplCrit.CLId = '".$campLiveId."'
                AND PAEmplCrit.IdTCrit = ".$ctvProductType."
                AND PAEmplCrit.RefCritere = '".$id_product_type."'
                GROUP BY Empl");
            $result = $stmt->execute();
            $NBEmpTotal = $stmt->rowCount($result);



            $stmt = $campLiveDb->prepare("SELECT PASej.Empl AS NbEmpOcc
            FROM PASej,PAEmplCrit
            WHERE PASej.CLId='".$campLiveId."'
            AND PAEmplCrit.CLId = PASej.CLId
            AND PAEmplCrit.IdTCrit = ".$ctvProductType."
            AND PAEmplCrit.Empl = PASej.Empl
            AND RefCritere='".$id_product_type."'
            AND NOT (PASej.Debut>=".$arrival_date.")
            AND NOT (PASej.Fin<=".$arrival_date.")
            GROUP BY PASej.Empl");

            $result1 = $stmt->execute();
            $NbEmpOcc = $stmt->rowCount($result1);


            if ($NBEmpTotal>$NbEmpOcc) {
                $value='TRUE';
                $Retour = '<aw_check_availability>
                                <state_product_type id_product_type="'.$id_product_type.'"
                                    arrival_date="'.$arrival_date.'"
                                    nights_number= "7"
                                    price="'.$price.'"
                                    id_company="'.$campLiveId.'"
                                    wording= "'.$talib.'" >
                                '.$value.'
                                </state_product_type>
                            </aw_check_availability>';
            } else {
                $value='FALSE';
            }
            file_put_contents($filepath, $Retour, FILE_APPEND);
        }
        return $value;
    }
}

$connexion = new Connexion();
$result2 = $connexion->getProducts();
$result3 = $connexion->checkAvailability($result2);
echo $result3;
