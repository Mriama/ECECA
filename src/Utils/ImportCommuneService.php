<?php

namespace App\Utils;

use App\Entity\RefDepartement;
use App\Entity\RefCommune;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * Classe d'import des communes
 *
 * @author Atef Mechri
 *
 */
class ImportCommuneService
{

    private $em; // EntityManager
    private $container; // Service container
    private $logger; // Service container

    public function __construct(ManagerRegistry $doctrine, Container $container, LoggerInterface $importCommuneLogger)
    {
        $this->em = $doctrine->getManager();
        $this->container = $container;
        $this->logger = $importCommuneLogger;
    }

    public function import($url)
    {
        try {
            // Récupération des paramètres
            $delimiter_ref_commune = $this->container->getParameter('ref_commune_file_delimiter');
            $traiteDir = $this->container->getParameter('ramsese_traite_dir');
            // Patterns des fichiers
            $refComPattern = $this->container->getParameter('communes_refcom_pattern');
            // Initialisation du logger
            $this->logger->info("Debut import fichier des communes...");
            $i = 0;
            if (strpos(basename($url), $refComPattern) === 0) {

                //basename($url) retourne le nom du fichier telecharge depuis url
                /**
                 * ************************** TRAITEMENT FICHIER DE TYPE uairefco (communes)****************************
                 */
                // traitement
                $nbCommuneInFile = 0;
                $nbErrorFormatLigne = 0;
                $nbLigneNonTraite = 0;
                $nbDoublonsFichier = 0;
                $arrayRefCommune = $this->em->getRepository(RefCommune::class)->getArrayRefCommuneByCodeInsee();
                $fh = fopen($url, 'r');
                $dataFile = array();
                $result = array();
                $arrayCodeInsee = array();

                while ($ligne = utf8_encode(fgets($fh))) {
                    $ligne = $this->eleminateSpaceTab($ligne);

                    $datas = explode($delimiter_ref_commune, $ligne);

                    $nbCommuneInFile++;
                    if (
                        is_array($datas) && sizeof($datas) == 5 //datas
                        && array_key_exists(4, $datas) && !empty($datas[4]) && strlen($datas[4]) <= 3//departement
                        && array_key_exists(0, $datas) && !empty($datas[0]) && strlen($datas[0]) <= 5 //code insee
                        && array_key_exists(2, $datas) && !empty($datas[2])&& strlen($datas[2]) <= 5 && is_numeric($datas[2]) && $datas[2] >= 1000 //code postal
                        && array_key_exists(1, $datas) && strlen($datas[1]) <= 32 //libelle
                    ) {
                        $array[$datas[0]][] = 1;
                        if ($this->traitementCodeDept($datas[4]) == null){
                            $nbLigneNonTraite++;
                            $this->logger->info("erreur de département: [".$datas[0]."] [".$datas[1]."] [".$datas[2]."] [".$datas[4]."]");
                        } else {
                            $dataFile[$datas[0]] = array(
                                'departement' => $this->traitementCodeDept($datas[4]),
                                'libelle' => $datas[1],
                                'code_postal' => $datas[2],
                                'code_insee' => $datas[0]
                            );

                            if (in_array($datas[0], $arrayCodeInsee)) {
                                $nbDoublonsFichier++;
                            }
                            $arrayCodeInsee[] = $datas[0];
                        }
                    } else {
                        if (is_array($datas) && sizeof($datas) == 5){
                            $logDepartement = array_key_exists(4, $datas) ? $datas[4] : "";
                            $logLibelle = array_key_exists(1, $datas) ? $datas[1] : "";
                            $logCodeInsee = array_key_exists(0, $datas) ? $datas[0] : "";
                            $logCodePostal = array_key_exists(2, $datas) ? $datas[2] : "";
                            $this->logger->info("Cette commune n’a pas pu être traitée correctement : [".$logCodeInsee."] [".$logLibelle."] [".$logCodePostal."] [".$logDepartement."]");
                            //$this->logger->info("le traitement ne s’arrête pas à la première erreur : tous les enregistrements du fichier des communes sont traités ");
                            $nbErrorFormatLigne++;
                        }
                    }

                }
                fclose($fh);

                // $this->logger->info("Nombre de code_insee en doublons dans le fichier CSV: " . $nbDoublonsFichier);
                $difference = $this->getDifferenceArrays($dataFile, $arrayRefCommune); //extract des communes selon traitement (ajout/edition/inchangées)
                $result = $this->insertOrMajCommunes($difference);

                $countRefCommuneInDbAfterImport = $this->em->getRepository(RefCommune::class)->countAllCommunes();

                if( is_array($result['arrayDeptErrorAjout']) && !empty($result['arrayDeptErrorAjout'])){
                    foreach($result['arrayDeptErrorAjout'] as $value){
                        $this->logger->info("erreur de départements causant un echec d'insertion de communes: [".$value['code_insee']."] [".$value['libelle']."] [".$value['code_postal']."]" );
                    }
                }
                if( is_array($result['arrayDeptErrorModification']) && !empty($result['arrayDeptErrorModification'])){
                    foreach($result['arrayDeptErrorModification'] as $value){
                        $this->logger->info("erreur de départements causant un echec de modification de communes: [".$value['code_insee']."] [".$value['libelle']."] [".$value['code_postal']."]" );
                    }
                }
                // 0240032: Batch RAMESE - Erreur dans la log - Problème de compteurs
                $this->logger->info($nbCommuneInFile. " communes lues dans le fichier CSV");
                $this->logger->info($nbDoublonsFichier. " communes en doublon sur le code INSEE dans le fichier CSV");
                $this->logger->info($nbErrorFormatLigne. " communes avec un mauvais format");
                $this->logger->info($nbLigneNonTraite. " communes non traitées");
                $this->logger->info($result['nb_communes_mise_a_jour']. " communes mises à jour dans la table ref_commune");
                if($result['nb_communes_doublons'] > 0) {
                    $this->logger->info($result['nb_communes_doublons']. " communes en doublons mises à jour dans la table ref_commune");
                }
                $this->logger->info($result['nb_communes_ajoutees'] . " communes insérées dans la table ref_commune");
                $this->logger->info($result['nb_communes_inchangees'] . " communes lues et inchangées dans la table ref_commune ");
                if ($result['nb_error_departement_modification'] > 0) {
                    $this->logger->info($result['nb_error_departement_modification'] . " erreurs de modification de communes causée(s) par des erreurs département ");
                }
                if ($result['nb_error_departement_ajout'] > 0) {
                    $this->logger->info($result['nb_error_departement_ajout'] . " erreurs d'ajout de communes causée(s) par des erreurs département ");
                }
                $this->logger->info($countRefCommuneInDbAfterImport. " communes présentes en table ref_commune en fin de traitement ");
                $fichierArchive = $this->getRootDir() . $traiteDir . date("YmdHi") . "_" . basename($url);
                rename($url, $fichierArchive);
            } else {
                $erreur = "Le format du fichier des communes n'est pas pris en charge par l'application : " . basename($url);
                $this->logger->error($erreur);
            }
            $this->logger->info("Fin import fichier communes");
        } catch (Exception $e) {
            $this->logger->error('KO :' . $e->getMessage());
        }
    }

    private function check_diff_multi($array1, $array2){
        $result = array();
        foreach($array1 as $key => $val) {
            if(isset($array2[$key])){
                if(is_array($val) && $array2[$key]){
                    $result[$key] = $this->check_diff_multi($val, $array2[$key]);
                }
            } else {
                $result[$key] = $val;
            }
        }

        return $result;
    }

    private function traitementCodeDept($dept)
    {
        if (is_numeric($dept) && $dept < 100) {
            return ltrim($dept, '0');
        } else {
            $subTwo = strtoupper(substr($dept, 0, 2));
            $subThree = strtoupper(substr($dept, 0, 3));
            if ($subTwo === '2A') {
                return '2A';
            } elseif ($subTwo === '2B') {
                return '2B';
            } elseif ($subTwo === '2B') {
                return '2B';
            } elseif ($subThree === '02A') {
                return '2A';
            } elseif ($subThree === '02B') {
                return '2B';
            } elseif ($subThree === '971') {
                return '9A';
            } elseif ($subThree === '972') {
                return '9B';
            } elseif ($subThree === '973') {
                return '9C';
            } elseif ($subThree === '974') {
                return '9D';
            } elseif ($subThree === '976' || $subThree === '985') {
                return '9E';
            } elseif ($subThree === '977') {
                return '9F';
            } elseif ($subThree === '978') {
                return '9G';
            } elseif ($subThree === '980') {
                return '99';
            } elseif ($subThree === '987') {
                return '9H';
            } elseif ($subThree === '988') {
                return '9I';
            } elseif ($subThree === '975') {
                return '9J';
            } else {
                return null;
            }
        }
    }

    private function getDifferenceArrays($array1, $array2){
        $new = array();
        $same = array();
        $difference = array();
        foreach($array1 as $key => $value){
            if(array_key_exists($key, $array2)){
                $communeExistante = $array2[$key];
                if(
                    strtoupper($array1[$key]['departement']) != strtoupper($communeExistante['departement'])
                    || strtoupper($array1[$key]['libelle']) != strtoupper($communeExistante['libelle'])
                    || $array1[$key]['code_postal'] != $communeExistante['code_postal']
                ){
                    $difference[$key] = $value;
                } else {
                    $same[$key] = $value;
                }
            } else {
                $new[$key] = $value;
            }
        }
        return array('maj' => $difference, 'new' => $new, 'same' => $same);
    }

    private function insertOrMajCommunes($datas){
        $arrayCommuneToUpdate = array();
        $arrayCommuneToInsert = array();
        $nbCommuneAjoutee = 0;
        $doublonCommuneUpdated = 0;
        $nbCommuneMiseAJour = 0;
        $nbCommuneInchangees = 0;
        $nbErrorImportCauseDepartementAjout = 0;
        $nbErrorImportCauseDepartementModification = 0;
        $arrayDeptErrorAjout = array();
        $arrayDeptErrorModification = array();

        //LES COMMUNES A AJOUTER
        if(is_array($datas['new']) && !empty($datas['new'])){
            foreach($datas['new'] as $key => $value){
                $departementRepo = $this->em->getRepository(RefDepartement::class)->find($datas['new'][$key]["departement"]);
                if ($departementRepo instanceof RefDepartement && !empty($departementRepo) && strlen($departementRepo->getNumero()) <= 3) {
                    $codeInsee = strtoupper($datas['new'][$key]["code_insee"]);
                    $libelle = strtoupper($datas['new'][$key]["libelle"]);
                    $codePostal = $datas['new'][$key]["code_postal"];

                    $nouvelleCommune = new refCommune;
                    $nouvelleCommune->setCodeInsee($codeInsee);
                    $nouvelleCommune->setCodePostal($codePostal);
                    $nouvelleCommune->setDepartement($departementRepo);
                    $nouvelleCommune->setLibelle($libelle);
                    array_push($arrayCommuneToInsert, $nouvelleCommune);
                    $nbCommuneAjoutee++;
                } else {
                    $arrayDeptErrorAjout[] = array(
                        'code_insee' => $datas['new'][$key]["code_insee"],
                        'libelle'    => $datas['new'][$key]["libelle"],
                        'code_postal'=> $datas['new'][$key]["code_postal"]
                    );
                    $nbErrorImportCauseDepartementAjout++;
                }
            }
        }
        //LES COMMUNES A METTRE A JOUR
        if(is_array($datas['maj']) && !empty($datas['maj'])){
            foreach($datas['maj'] as $key => $value){
                $departementRepo = $this->em->getRepository(RefDepartement::class)->find($datas['maj'][$key]["departement"]);
                if ($departementRepo instanceof RefDepartement && !empty($departementRepo) && strlen($departementRepo->getNumero()) <= 3) {
                    $isExist = $this->em->getRepository(RefCommune::class)->findBy(array('codeInsee' => $key));
                    $doublonCommune = sizeof($isExist) > 1 ? true : false;
                    if(!empty($isExist)) {
                        foreach ($isExist as $k => $commune){
                            $codeInsee = strtoupper($datas['maj'][$key]["code_insee"]);
                            $libelle = strtoupper($datas['maj'][$key]["libelle"]);
                            $codePostal = $datas['maj'][$key]["code_postal"];
                            if ($doublonCommune) {
                                $doublonCommuneUpdated++;
                            }
                            $commune->setCodeInsee(strtoupper($codeInsee));
                            $commune->setCodePostal($codePostal);
                            $commune->setDepartement($departementRepo);
                            $commune->setLibelle(strtoupper($libelle));
                            array_push($arrayCommuneToUpdate, $commune);
                            // $nbCommuneMiseAJour++;
                        }
                        // CORRECTION 0240032: Batch RAMESE - Erreur dans la log - Problème de compteurs
                        $nbCommuneMiseAJour++;
                    }
                } else {
                    $arrayDeptErrorModification[] = array(
                        'code_insee' => $datas['maj'][$key]["code_insee"],
                        'libelle'    => $datas['maj'][$key]["libelle"],
                        'code_postal'=> $datas['maj'][$key]["code_postal"]
                    );
                    $nbErrorImportCauseDepartementModification++;
                }
            }
        }

        //LES COMMUNES INCHANGEES
        if(is_array($datas['same']) && !empty($datas['same'])){
            $nbCommuneInchangees = sizeof($datas['same']);
        }
        if (sizeof($arrayCommuneToUpdate) > 0) {
            $this->em->getRepository(RefCommune::class)->insertListeRefCommunesByImport($arrayCommuneToUpdate);
        }

        if (sizeof($arrayCommuneToInsert) > 0) {
            $this->em->getRepository(RefCommune::class)->insertListeRefCommunesByImport($arrayCommuneToInsert);
        }
        $result = array(
            'nb_communes_ajoutees' => $nbCommuneAjoutee,
            'nb_communes_mise_a_jour' => $nbCommuneMiseAJour,
            'nb_communes_inchangees' => $nbCommuneInchangees,
            'nb_error_departement_modification' => $nbErrorImportCauseDepartementModification,
            'nb_error_departement_ajout' => $nbErrorImportCauseDepartementAjout,
            'nb_communes_doublons' => $doublonCommuneUpdated,
            'arrayDeptErrorAjout' => $arrayDeptErrorAjout,
            'arrayDeptErrorModification' => $arrayDeptErrorModification
        );
        return $result;
    }

    private function eleminateSpaceTab($ligne){
        $ligne = str_replace("\t", '', $ligne); // remove tabs
        $ligne = str_replace("\n", '', $ligne); // remove new lines
        $ligne = str_replace("\r", '', $ligne); // remove carriage returns
        return $ligne;
    }

    protected function getRootDir()
    {
        return __DIR__ . "/../../public/";
    }
}