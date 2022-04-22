<?php

namespace App\Utils;

use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefEtablissement;

abstract class EpleUtils {

    const TOUTES_ZONES = 'nationale'; // ne pas oublier de modifier dans eple.js

    /**
     * Fonction pour obtenir la zone correspondante
     * @param  $idZone : TOUTES_ZONES ou identifiants sur 2 ou 3 caractères
     */
    public static function getZone($em, $idZone = null) {
        $zone = null;
        if ($idZone === self::TOUTES_ZONES) {
            $zone = self::TOUTES_ZONES;
        } elseif($idZone != null) {
            $zone = $em->getRepository(RefDepartement::class)->find($idZone);
            if ($zone == null) {
                $zone = $em->getRepository(RefAcademie::class)->find($idZone);
            }
            if ($zone == null) {
                $zone = $em->getRepository(RefEtablissement::class)->find($idZone);
            }
        }
        return $zone;
    }

    /**
     * Fonction pour vérifier que $email est un email
     * @param  $email : string
     * @return 0 ou 1
     */
    public static function isEmailValid($email) {
        $expRegEmail = '/^[[:alnum:]]([-_.]?[[:alnum:]])+_?@[[:alnum:]]([-.]?[[:alnum:]])+\.[a-z]{2,6}$/';
        return preg_match($expRegEmail, $email);
    }

    /**
     *
     * @param $departements
     */
    public static function getNumerosDepts($departements){
        $depts = array();
        foreach($departements as $dept){
            array_push($depts, '\''.$dept->getNumero().'\'');
        }
        return implode(",", $depts);
    }

    public static function getUais($etablissements) {
        $stringUais = "";
        foreach ($etablissements as $etab) {
            $stringUais .= "'" . $etab->getUai() . "',";
        }
        $stringUais = substr($stringUais, 0,  strlen($stringUais) - 1);

        return $stringUais;
    }

    public static function getAcademieCodeFromDepartement(RefDepartement $departement) {
        $aca = $departement->getAcademie();
        if($aca->getAcademieFusion() != null) {
            return $aca->getAcademieFusion()->getCode();
        } else {
            return $aca->getCode();
        }
    }

    public static function isAcademie($idZone) {
        $zone = EpleUtils::getZone($idZone);
        return $zone instanceof RefAcademie;
    }

}