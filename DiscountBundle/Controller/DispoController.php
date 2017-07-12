<?php

namespace DiscountBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DomCrawler\Crawler;


class DispoController extends Controller
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
    */
    public function getNextSaturday($week, $year, $monday, $count)
    {
        $saturday = date('m-d', strtotime($count . ' day', strtotime($monday)));
        return $saturday;
    }



    /**
    * @Route("/dispoResponse", name="dispo_response")
    */
    public function dispoResponseAction()
    {

        $campLiveId = $this->get("security.token_storage")->getToken()->getUser()->getCampLiveId();

        $lien = 'http://camplive.com/admin/destockage.php';
        $user= 'camplive';
        $pass = 'pa98mico';

        $year = date("Y");
        $week = $this->countWeekAction();


        $dateSaturday1 = $year. "-" .$this->getNextSaturday($week, $year, $this->getMonday($week, $year), 5);
        $postfields1 = [
            'id_company' => $campLiveId,
            'arrival_date' => $dateSaturday1,
            'nights_number' => '7',
        ];

        $curl=curl_init();

        curl_setopt($curl, CURLOPT_URL, $lien);
        curl_setopt($curl, CURLOPT_USERPWD, "$user:$pass");
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields1);

        $return1 = curl_exec($curl);



        $dateSaturday2 = $year. "-" .$this->getNextSaturday($week, $year, $this->getMonday($week, $year), 12);
        $postfields2 = [
            'id_company' => $campLiveId,
            'arrival_date' => $dateSaturday2,
            'nights_number' => '7',
        ];

        curl_setopt($curl, CURLOPT_URL, $lien);
        curl_setopt($curl, CURLOPT_USERPWD, "$user:$pass");
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields2);

        $return2 = curl_exec($curl);


        $dateSaturday3 = $year. "-" .$this->getNextSaturday($week, $year, $this->getMonday($week, $year), 19);
        $postfields3 = [
            'id_company' => $campLiveId,
            'arrival_date' => $dateSaturday3,
            'nights_number' => '7',
        ];

        curl_setopt($curl, CURLOPT_URL, $lien);
        curl_setopt($curl, CURLOPT_USERPWD, "$user:$pass");
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields3);

        $return3 = curl_exec($curl);

        curl_close($curl);

         return $this->render('DiscountBundle:Default:dispo.html.twig',[
            'return1' => $return1,
            'return2' => $return2,
            'return3' => $return3,
         ]);

    }





}
