<?php

namespace App\Utils;

use DateTime;

abstract class EcecaExportUtils {

    /**
     * Fonction qui gènère le nom du fichier (sans l'extension) PDF ou XLS à exporter
     * @param  $typeExport : string (Resultats ou Statistiques)
     * @param  $params : array
     */
    public static function generateFileName($typeExport, $params){

        $nomZone = '';

        // On teste si la recherche est effectué par zone ou par établissement
        if (!empty($params['electZone'])) {
            $elect = $params['electZone'];
            if (!empty($params['nationale'])) {
                $nomZone = 'Nationales';
            } elseif (!empty($params['commune'])) {
                $nomZone = 'commune'.$params['commune']->getId();
            } elseif (!empty($params['dept'])) {
                $nomZone = 'dpt'.$params['dept']->getNumero();
            } elseif (!empty($params['aca'])) {
                $nomZone = $params['aca']->getCode();
            }
        } else {
            // YME - #0145360
            $elect = @$params['electEtablissement'];
            $electPrec = @$params['electEtablissementPrec'];
            if($elect != null){
                $nomZone = $elect->getEtablissement()->getUai();
            }elseif($electPrec != null){
                $nomZone = $electPrec->getEtablissement()->getUai();
            }
        }
        if($elect != null){
            $campagne = $elect->getCampagne();
        }elseif($electPrec != null){
            $campagne = $electPrec->getCampagne();
        }
        //NomZone update pour academies de fusion
        if(!empty($params['electZone']) && empty($params['nationale']) && !empty($params['aca'])) {
            $filtre_aca = $params['aca'];
            $dateDebutCampagne = new DateTime($campagne->getAnneeDebut() . "-01-01");
            if($filtre_aca->getDateDesactivation() <= $dateDebutCampagne && $filtre_aca->getAcademieFusion() != null) {
                $filtre_aca = $filtre_aca->getAcademieFusion();
            }
            $nomZone = $filtre_aca->getCode();
        }

        $typeElect = $params['typeElect'];

        //Remplacement des caractères spéciaux pour le type d'élection
        $typeElect = str_replace(' ', '_', $typeElect->getCode());

        if (array_key_exists('sousTypeElect', $params)) {
            $sousTypeElect = $params['sousTypeElect'];
            $sousTypeElect = str_replace(' ', '_', $sousTypeElect->getCode());
            if (array_key_exists('detaille', $params)) {
                return $typeExport.'_Election_par_etab_liste_'.$sousTypeElect.'_'.$nomZone.'_'.$campagne->getAnneeDebut() . '-' . $campagne->getAnneeFin();
            }
            return $typeExport.'_Elections_'.$sousTypeElect.'_'.$nomZone.'_'.$campagne->getAnneeDebut() . '-' . $campagne->getAnneeFin();
        } else {
            if (array_key_exists('detaille', $params)) {
                return $typeExport.'_Election_par_etab_liste_'.$typeElect.'_'.$nomZone.'_'.$campagne->getAnneeDebut() . '-' . $campagne->getAnneeFin();
            }
            return $typeExport.'_Elections_'.$typeElect.'_'.$nomZone.'_'.$campagne->getAnneeDebut() . '-' . $campagne->getAnneeFin();
        }
    }

    public static function getAnneeScolaireEncours(){
        $today = new \DateTime ();
        $nowYear = $today->format ( 'Y' );
        $nextYear = date('Y', strtotime('+1 year'));
        return $nowYear."-".$nextYear;
    }
}