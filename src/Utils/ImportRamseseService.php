<?php
namespace App\Utils;

use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefEtablissement;
use App\Entity\RefTypeEtablissement;
use App\Entity\RefZoneNature;
use App\Repository\RefEtablissementRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Repository\RefZoneNatureRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\RefCommune;
use Psr\Log\LoggerInterface;
use Doctrine\Tests\DBAL\Types\VarDateTimeTest;

/**
 * Classe d'import ramsese
 *
 * @author a176206
 *        
 */
class ImportRamseseService
{

    private $em; // EntityManager
    private $container; // Service container
	private $logger; // Log
    public function __construct(EntityManagerInterface $em, ContainerInterface $container,LoggerInterface $logger)
    {
        $this->em = $em;
        $this->container = $container;
		$this->logger = $logger;
    }

    public function import($url)
    {
        
        // Récupération des paramètres
        $delimiter = $this->container->getParameter('ramsese_file_delimiter');
        $uploadDir = $this->container->getParameter('ramsese_upload_dir');
        $traiteDir = $this->container->getParameter('ramsese_traite_dir');
        
        // Patterns des fichiers
        $etabPattern = $this->container->getParameter('ramsese_etab_pattern');
        $commPattern = $this->container->getParameter('ramsese_comm_pattern');
        
        // Initialisation du logger
        
        $this->logger->info("Debut import fichier ...");
        
        // Recuperation des infos dans les tables RefEtablissement, EleEtablissement, RefZoneNature, RefTypeEtablissement, RefCommune
        
       // Recuperation de UAI de tous les etablissements
        $arrayRefEtablissement = $this->em->getRepository(RefEtablissement::class)->getArrayRefEtablissementUai();
        $this->logger->info("nb uai refEtablissement : " . sizeof($arrayRefEtablissement));
    	
        
      //  Recuperation de ID commune de tous les etablissements
        $arrayRefEtablissementCommune = $this->em->getRepository(RefEtablissement::class)->getArrayRefEtablissementUaiIdCommune();
        
        //  Recuperation de ID type prioritaire de tous les etablissements
        $arrayRefEtablissementTypePrioritaire = $this->em->getRepository(RefEtablissement::class)->getArrayRefEtablissementUaiIdTypePrioritaire();
        
        //Recuperation de ID commune de tous les etablissements
        $arrayEleEtablissement = $this->em->getRepository(EleEtablissement::class)->getArrayEleEtablissementUai();
        $logger->info("nb total distinct uai eleEtablissement : " . sizeof($arrayEleEtablissement));
        
        $listeRefZoneNature = $this->em->getRepository(RefZoneNature::class)->findAll();
        $arrayRefZoneNature = array();
        foreach ($listeRefZoneNature as $refZoneNature) {
            $arrayRefZoneNature[$refZoneNature->getUaiNature()] = $refZoneNature;
        }
        $logger->info("nb refZoneNature :" . sizeof($arrayRefZoneNature));
        
        $listeRefTypeEtablissement = $this->em->getRepository(RefTypeEtablissement::class)->findAll();
        $arrayRefTypeEtablissement = array();
        foreach ($listeRefTypeEtablissement as $refTypeEtablissement) {
            $arrayRefTypeEtablissement[$refTypeEtablissement->getId()] = $refTypeEtablissement;
        }
    	$logger->info("nb refTypeEtablissement :" . sizeof($arrayRefTypeEtablissement));
        
        $arrayRefCommune = $this->em->getRepository(RefCommune::class)->getArrayRefCommune();
    	$logger->info("nb refCommune :" . sizeof($arrayRefCommune));
        
        $retour = array();
        
        $retour['nbEtabAdd'] = 0;
        $retour['nbEtabUpdate'] = 0;
        $retour['nbEtabDelete'] = 0;
        $retour['nbEtabDesactive'] = 0;
        $retour['nbEtabRejete'] = 0;
        $retour['nbCommunesNonTrouvees'] = 0;
       
        if (strpos(basename($url), $etabPattern) === 0) {
        	//basename($url) retourne le nom du fichier telecharge depuis url  
            /**
             * ************************** TRAITEMENT FICHIER DE TYPE UAIS ****************************
             */
            
            $retour['info'] = $this->container->getParameter('info');
            
            // traitement
            $listeRefEtablissementToInsert = array();
            $listeRefEtablissementToUpdate = array();
            $nbEtabsToUpdate = array();
            $nbEtabsToInsert = array();
            
            $fh = fopen($url, 'r'); //on lit le fichier en mode de lecture 
            
            while ($ligne = utf8_encode(fgets($fh))) { //on verifie s'il reste encore du contenu a lire
                // Récupération des données de l'établissement depuis le fichier en entree
                $datas = explode($delimiter, $ligne); // on decoupe le fichier par deliminateur "|"
               
                if (sizeof($datas) == 37) { // on verifie si le fichier contient bien 36 infos
                    if (strlen($datas[0]) == 8) { // on verifie si la premiere info du fichier est bien de 8 caractere (car longth(UAI) = 8)
                        $refEtabUai = null;
                        if (array_key_exists($datas[0], $arrayRefEtablissement)) { // on verifie si UAI de l'etablissement existe dans la BD
                            $refEtabUai = $arrayRefEtablissement[$datas[0]]; 
                        }
                        
                        // Seuls les etablissements publics sont traites : SECTCO (champ num 7) = 'PU'
                        if ($datas[6] == 'PU') {
                            
                            // Si l'établissement existe deja en base
                            if ($refEtabUai != null) {
                            	// BBL commente : 014E le code mouvement est toujours à I
                                /* // Si le code mouvement est à Suppression
                                if ($datas[1] == "S") {
                                    // on récupere l'établissement associé
                                    $eleEtabUai = null;
                                    if (array_key_exists($refEtabUai, $arrayEleEtablissement)) {
                                        $eleEtabUai = $arrayEleEtablissement[$refEtabUai];
                                    }
                                    
                                    // Si l'établissement existe dans la table EleEtablissement
                                    if ($eleEtabUai != null) {
                                        // on désactive l'établissement
                                        array_push($listeRefEtablissementToDesactive, $refEtabUai);
                                 		$logger->error($datas[0] . ": impossible de supprimer cet établissement car il existe dans la base ele_etablissement, il est désactivé");
                                        $retour['nbEtabDesactive'] ++;
                                    } else {
                                        // on supprime l'établissement
                                        // 014E il faut plus supprimer l'etablissement mais seulement le désactiver actif = 0
                                        array_push($listeRefEtablissementToRemove, $refEtabUai);
                                        $retour['nbEtabDelete'] ++;
                                    }
                                }*/
                            	// 014E RG_IMPORT_1514 si l'etab est présent plusieurs fois seul le premier est traité

                            	// BBL 014E le code mouvement est toujours à I
                            	if ($datas[1] == "I") {
                            			// si le code nature existe en base
	                            		if (array_key_exists($datas[3], $arrayRefZoneNature)) {

	                            			$zoneNature = $arrayRefZoneNature[$datas[3]];
	                            			
	                            			// il faut que le type nature champ 4 correspond au code nature champ 3
	                            			if ($zoneNature->getTypeNature() == $datas[4]) {
	                            				
	                            				// le type d'établissement est défini
	                            				switch ($datas[4]) {
	                            					case "1ORD":
	                            						$refId = 1;
	                            						break;
	                            						// Traitement des types APPL comme 1ORD
	                            					case "APPL":
	                            						$refId = 1;
	                            						break;
	                            						// Traitement des types SPEC comme 1ORD
	                            					case "SPEC":
	                            						$refId = 1;
	                            						break;
	                            					case "CLG":
	                            						$refId = 2;
	                            						break;
	                            					case "LYC":
	                            						$refId = 3;
	                            						break;
	                            					case "LP":
	                            						$refId = 4;
	                            						break;
	                            					case "EREA":
	                            						$refId = 5;
	                            						break;
	                            					case "ERPD":
	                            						$refId = 5;
	                            						break;
	                            					default:
	                            						$refId = 0;
	                            				}
	                            				 
	                            				// Mise à jour des données de l'établissement  Code I
	                            				$refEtab = new RefEtablissement();  /*====Traitement======*/
	                            				$refEtab->setUai($refEtabUai); /*=========Traitement 1 d'UAI=============*/
	                            				 
	                            				   
	                            				/*====Traitement de Libelle=====*/
	                            				//Détermination du libellé par application de la règle BE1D (RG_IMPORT_09)
	                            				//Optimisation de performance pour l'import RAMSESE
	                            				if (null != $datas[8]) {
	                            					$refEtab->setLibelle($datas[8]);
	                            				} elseif (null != $datas[7]) {
	                            					$refEtab->setLibelle($datas[7]);
	                            				} elseif (null != $datas[11]) {
	                            					$refEtab->setLibelle($datas[11]);
	                            				} else {
	                            					if (array_key_exists($datas[3], $arrayRefZoneNature)) {
	                            						$zoneNature = $arrayRefZoneNature[$datas[3]];
	                            						$refEtab->setLibelle($zoneNature->getLibelleLong());
	                            					}
	                            				}//Fin traitement libelle
	                            				 
	                            				$refEtab->setContact($datas[25]); /*====Traitement 3 de contact=====*/
	                            				 
	                            				   
	                            				     
	                            				/*====Traitement de la date de fermeture=====*/
	                            				//conversion du format francais en format americain pour la BD
	                            				if(!empty($datas[21])){
	                            					$dateEn = substr($datas[21], 4, 7).substr($datas[21], 2, 2).substr($datas[21], 0, 2);
	                            					 
	                            					$dateYear = (int)substr($datas[21], 4, 7);
	                            					$dateMonth = (int)substr($datas[21], 2, 2);
	                            					$dateDay = (int)substr($datas[21], 0, 2);
	                            					 
	                            					$dateFermeture = new \DateTime();
	                            					$dateFermeture->setDate($dateYear, $dateMonth, $dateDay);
	                            					$refEtab->setDateFermeture($dateFermeture);
	                            					 
	                            				}else{
	                            					 
	                            					$dateFermeture = new \DateTime();
	                            					$dateFermeture->setDate(null, null, null);
	                            					$refEtab->setDateFermeture($dateFermeture);
	                            				}//Fin du traitement de la date de fermeture
	                            				 
	                            				 
	                            				if (array_key_exists($datas[3], $arrayRefZoneNature)) { /*====Traitement 4 de type proprietaire=====*/
	                            					$typeNature = $arrayRefZoneNature[$datas[3]];
	                            					$refEtab->setTypePrioritaire($typeNature->getTypePrioritaire());
	                            				}
	                            			
	                            				/*=====Traitement Zone Nature ===============*/
	                            				$refEtab->setUaiNature($typeNature);
	                            				 
	                            				 
	                            				if ($refId == 0) {
	                            					// on garde l'ancien type d'établissement
	                            					$lastTypeEtablissement = $this->em->getRepository(RefEtablissement::class)->find($refEtabUai)->getTypeEtablissement();
	                            					$refEtab->setTypeEtablissement($lastTypeEtablissement);
	                            					// si le type de l'etablissement n'est pas autorisé on désactive automatiquement l'etab
	                            					$refEtab->setActif(0);
	                            				} else {
	                            			
	                            					// 1 actif (ouvert); 2 à fermer; 3 à ouvrir et 4 fermé
	                            					$refEtab->setActif($datas[19] == 1 ? 1 : 0);		/*====Traitement 5 de l'etat actif ou non actif=====*/
	                            			
	                            					// RG_IMPORT_10 : Les établissements fermés au 31 août de l'année en cours sont inactifs
	                            					// i.e UAIDTF inférieure ou égale au 31 août de l’année en cours
	                            					if (!empty($datas[21])) {
	                            						$dateFermeture->setTime(0, 0, 0);
	                            						$dateLimiteFermeture = new \DateTime();
	                            						$dateLimiteFermeture->setDate(date("Y"), 8, 31);
	                            						$dateLimiteFermeture->setTime(0, 0, 0);
	                            						if ($dateFermeture <= $dateLimiteFermeture) {
	                            							$refEtab->setDateFermeture($dateFermeture);
	                            							$refEtab->setActif(0);
	                            						}
	                            						
                            						    // Anomalie 0181007
                                                        // Ajout d'une nouvelle règle (si le code est différent de 1, et qu'il y a une date de fermeture renseignée)
                                                        // Prendre la date du 1er octobre comme date charnière :
														// Si la date de fermeture est postérieure au 1er octobre N, l'établissement reste actif pour la campagne N et ne sera fermé que pour la campagne N+1.
														// Si la date de fermeture est antérieure au 30 septembre N, l'établissement sera alors inactif	                            							
                                                        if (!empty($datas[19]) AND $datas[19] != 1) {
                                                        			
                            								$dateLimiteFermeture2 = new \DateTime();
                            								// Date charnière
                            								$dateLimiteFermeture2->setDate(date("Y"), 9, 30);
                            								$dateLimiteFermeture2->setTime(0, 0, 0);		
                                                        
                                                        	$logger->info('Valeur du code : '.$datas[19]);
                                                        	$logger->info('Date limite : '.$dateLimiteFermeture2->format('Y-m-d'));
                                                        	$logger->info('Date fermeture : '.$dateFermeture->format('Y-m-d'));
                                                        			
															if ($dateFermeture > $dateLimiteFermeture2) {
																$logger->info('Cas actif '.$datas[0]);
                                                            	$refEtab->setDateFermeture($dateFermeture);
                                                                $refEtab->setActif(1);
                                                            } else {
                                                            	$logger->info('Cas inactif '.$datas[0]);
                                                            	$refEtab->setDateFermeture($dateFermeture);
                                                            	$refEtab->setActif(0);
                                                            }
                                                        }
	                            					}
	                            				}
	                            				 
	                            				if (array_key_exists($refId, $arrayRefTypeEtablissement)) {  /*====Traitement 6 de type proprietaire=====*/
	                            					$typeEtablissement = $arrayRefTypeEtablissement[$refId];
	                            					$refEtab->setTypeEtablissement($typeEtablissement);
	                            				}
	                            			
	                            				array_push($listeRefEtablissementToUpdate, $refEtab);
	                            			
	                            				// on compte le nombre des établissements mis à jour et non pas le nombre des enregistrements dans le fichier
	                            				if (!array_key_exists($refEtabUai, $nbEtabsToUpdate)) {
	                            					$retour['nbEtabUpdate'] ++;
	                            				}
	                            				$nbEtabsToUpdate[$refEtabUai] = $refEtab;
	                            				 
	                            			}
	                            			else {
	                            				$logger->error($datas[0] . ": impossible de traiter ce type d'établissement, le code nature et le type nature ne sont pas cohérent.");
	                            				$retour['nbEtabRejete'] ++;
	                            			}
	                            		} else {
	                            			$logger->error($datas[0] . ": impossible de traiter ce type d'établissement, le code nature est inconnu.");
	                            			$retour['nbEtabRejete'] ++;
	                            		}
                            	} else {
                            		$logger->error($datas[0] . ": impossible de traiter ce type d'établissement");
                            		$retour['nbEtabRejete'] ++;
                            	}
                            	// BBL commente : 014E le code mouvement est toujours à I
                            	/* // Si le code mouvement est à Création
                            	 if ($datas[1] == "C") {
                            	
                            	$logger->error($datas[0] . ": l'Etablissement existe déja dans la base");
                            	}*/
                            	               
                            } else { // l'établissement n'existe pas dans la table RefEtablissement
                            	
                            	// si le code nature existe en base
                            	if (array_key_exists($datas[3], $arrayRefZoneNature)) {
                            	
                            		$zoneNature = $arrayRefZoneNature[$datas[3]];
                            	
                            		// il faut que le type nature champ 4 correspond au code nature champ 3
                            		if ($zoneNature->getTypeNature() == $datas[4]) {

                            			// Si le code mouvement est à Création ou Initialisation
                            			if ($datas[1] == "I") {
                            				// Si le type d'établissement est défini on le traite, sinon, on log et on passe au suivant
                            				switch ($datas[4]) {
                            					case "1ORD":
                            						$refId = 1;
                            						break;
                            						// Traitement des types APPL comme 1ORD
                            					case "APPL":
                            						$refId = 1;
                            						break;
                            						// Traitement des types SPEC comme 1ORD
                            					case "SPEC":
                            						$refId = 1;
                            						break;
                            					case "CLG":
                            						$refId = 2;
                            						break;
                            					case "LYC":
                            						$refId = 3;
                            						break;
                            					case "LP":
                            						$refId = 4;
                            						break;
                            					case "EREA":
                            						$refId = 5;
                            						break;
                            					case "ERPD":
                            						$refId = 5;
                            						break;
                            					default:
                            						$refId = 0;
                            				}
                            			
                            				if ($refId == 0) {
                            					$logger->error($datas[0] . ": impossible de traiter ce type d'établissement");
                            					$retour['nbEtabRejete'] ++;
                            				} else {
                            					// Création de l'établissement
                            					$refEtab = new RefEtablissement();
                            					$refEtab->setUai($datas[0]);
                            			
                            					/*====Traitement de Libelle=====*/
                            					//Détermination du libellé par application de la règle BE1D (RG_IMPORT_09)
                            					//Optimisation de performance pour l'import RAMSESE
                            					if (null != $datas[8]) {
                            						$refEtab->setLibelle($datas[8]);
                            					} elseif (null != $datas[7]) {
                            						$refEtab->setLibelle($datas[7]);
                            					} elseif (null != $datas[11]) {
                            						$refEtab->setLibelle($datas[11]);
                            					} else {
                            						if (array_key_exists($datas[3], $arrayRefZoneNature)) {
                            							$zoneNature = $arrayRefZoneNature[$datas[3]];
                            							$refEtab->setLibelle($zoneNature->getLibelleLong());
                            						}
                            					}//Fin traitement libelle
                            			
                            			
                            					 
                            					/*====Traitement de la date de fermeture=====*/
                            					//conversion du format francais en format americain pour la BD
                            					if(!empty($datas[21])){
                            						$dateEn = substr($datas[21], 4, 7).substr($datas[21], 2, 2).substr($datas[21], 0, 2);
                            						 
                            						$dateYear = (int)substr($datas[21], 4, 7);
                            						$dateMonth = (int)substr($datas[21], 2, 2);
                            						$dateDay = (int)substr($datas[21], 0, 2);
                            						 
                            						$dateFermeture = new \DateTime();
                            						$dateFermeture->setDate($dateYear, $dateMonth, $dateDay);
                            						$refEtab->setDateFermeture($dateFermeture);
                            						 
                            					} else {
                            			
                            						$dateFermeture = new \DateTime();
                            						$dateFermeture->setDate(null, null, null);
                            						$refEtab->setDateFermeture($dateFermeture);
                            					} //Fin du traitement de la date de fermeture
                            			
                            			
                            					 
                            			
                            					$refEtab->setContact($datas[25]);
                            			
                            			
                            					if (array_key_exists($datas[3], $arrayRefZoneNature)) { //
                            						$typeNature = $arrayRefZoneNature[$datas[3]];
                            						$refEtab->setTypePrioritaire($typeNature->getTypePrioritaire());
                            			
                            					}
                            			
                            					/*=====Traitement Zone Nature ===============*/
                            					 
                            					$refEtab->setUaiNature($typeNature);
                            			
                            			
                            			
                            					// 1 actif (ouvert); 2 à fermer; 3 à ouvrir et 4 fermé
                            					$refEtab->setActif($datas[19] == 1 ? 1 : 0);
                            			
                            					// RG_IMPORT_10 : Les établissements fermés au 31 août de l'année en cours sont inactifs
                            					// i.e UAIDTF inférieure ou égale au 31 août de l’année en cours
                            					if (!empty($datas[21])) {
                            						$dateFermeture->setTime(0, 0, 0);
                            						$dateLimiteFermeture = new \DateTime();
                            						$dateLimiteFermeture->setDate(date("Y"), 8, 31);
                            						$dateLimiteFermeture->setTime(0, 0, 0);
                            						if ($dateFermeture <= $dateLimiteFermeture) {
                            							$refEtab->setDateFermeture($dateFermeture);
                            							$refEtab->setActif(0);
                            						}
                            						
                        						    // Anomalie 0181007
                                                    // Ajout d'une nouvelle règle (si le code est différent de 1, et qu'il y a une date de fermeture renseignée)
                                                    // Prendre la date du 1er octobre comme date charnière :
													// Si la date de fermeture est postérieure au 1er octobre N, l'établissement reste actif pour la campagne N et ne sera fermé que pour la campagne N+1.
													// Si la date de fermeture est antérieure au 30 septembre N, l'établissement sera alors inactif	                            							
                                                    if (!empty($datas[19]) AND $datas[19] != 1) {
                                                    			
                        								$dateLimiteFermeture2 = new \DateTime();
                        								// Date charnière
                        								$dateLimiteFermeture2->setDate(date("Y"), 9, 30);
                        								$dateLimiteFermeture2->setTime(0, 0, 0);			
                                                        
                                                    	$logger->info('Valeur du code : '.$datas[19]);
                                                    	$logger->info('Date limite : '.$dateLimiteFermeture2->format('Y-m-d'));
                                                    	$logger->info('Date fermeture : '.$dateFermeture->format('Y-m-d'));
                                                        			
														if ($dateFermeture > $dateLimiteFermeture2) {
															$logger->info('Cas actif '.$datas[0]);
                                                        	$refEtab->setDateFermeture($dateFermeture);
                                                            $refEtab->setActif(1);
                                                        } else {
                                                        	$logger->info('Cas inactif '.$datas[0]);
                                                        	$refEtab->setDateFermeture($dateFermeture);
                                                        	$refEtab->setActif(0);
                                                        }
                                                    }
                            					}
                            			
                            					if (array_key_exists($refId, $arrayRefTypeEtablissement)) {
                            						$typeEtablissement = $arrayRefTypeEtablissement[$refId];
                            						$refEtab->setTypeEtablissement($typeEtablissement);
                            					}
                            			
                            					array_push($listeRefEtablissementToInsert, $refEtab);
                            			
                            					if (!array_key_exists($datas[0], $nbEtabsToInsert))
                            						$retour['nbEtabAdd'] ++;
                            					$nbEtabsToInsert[$refEtab->getUai()]= $refEtab;
                            				}
                            			} else {
                            				$logger->error($datas[0] . ": impossible de traiter ce type d'établissement");
                            				$retour['nbEtabRejete'] ++;
                            			}
                            		}
	                                else {
	                                	$logger->error($datas[0] . ": impossible de traiter ce type d'établissement, le code nature et le type nature ne sont pas cohérent.");
	                                	$retour['nbEtabRejete'] ++;
	                                }
                                } else {
                                	$logger->error($datas[0] . ": impossible de traiter ce type d'établissement, le code nature est inconnu.");
                                	$retour['nbEtabRejete'] ++;
                                }
                                // BBL commente : 014E le code mouvement est toujours à I
                                /* // Si le code mouvement est à Modification
                                if ($datas[1] == "M") {
                                   	$logger->error($datas[0] . ": impossible de modifier un établissement non-existant dans la base");
                                }
                                // Si le code mouvement est à Suppression
                                if ($datas[1] == "S") {
                                    $logger->error($datas[0] . ": impossible de supprimer un établissement non-existant dans la base");
                                }*/
                            }
                        } else {
                           $logger->error($datas[0] . ": Cet établissement est un établissement non public.");
                            $retour['nbEtabRejete'] ++;
                        }
                    } else {
                        $logger->error($datas[0] . ": l'UAI de cet établissement est incorrecte, vérifier le nombre de caractére du champ UAI");
                        $retour['nbEtabRejete'] ++;
                    }
                } else {
                  	$logger->error($datas[0] . ": la ligne des informations pour cet établissement est incorrecte, vérifier le nombre de champs de données");
                    $retour['nbEtabRejete'] ++;
                }
            }
            fclose($fh);
            
            if (sizeof($listeRefEtablissementToInsert) > 0) {
                $this->em->getRepository(RefEtablissement::class)->insertListeRefEtablissementByRamsese($listeRefEtablissementToInsert);
            }
            
            if (sizeof($listeRefEtablissementToUpdate) > 0) {
            	$this->em->getRepository(RefEtablissement::class)->insertListeRefEtablissementByRamsese($listeRefEtablissementToUpdate);
            }
            
            // 014E il faut plus supprimer les établissement mais plutot les désactiver actif = 0
            /*if (sizeof($listeRefEtablissementToRemove) > 0) {
                $this->em->getRepository(RefEtablissement::class)->removeListeRefEtablissementByRamsese($listeRefEtablissementToRemove);
            }*/
            
            /*if (sizeof($listeRefEtablissementToDesactive) > 0) {
                $this->em->getRepository(RefEtablissement::class)->desactiveListeRefEtablissementByRamsese($listeRefEtablissementToDesactive);
            }*/
            
            // déplacer le fichier traité
            $fichierArchive = $this->getRootDir() . $traiteDir . date("YmdHi") . "_" . basename($url);
            rename($url, $fichierArchive);
            
            // resultat du traitement
           	$logger->info("Résultat du traitement : " . $retour['nbEtabUpdate'] . " établissements sont mis à jour, " . $retour['nbEtabAdd'] . " ajoutés et " . $retour['nbEtabRejete'] . " rejetés.");
        } else //On commence a traiter les fichiers UAIRATT
            if (strpos(basename($url), $commPattern) === 0) {
                
                /**
                 * ************************** TRAITEMENT FICHIER DE TYPE UAIRATT ****************************
                 */
                
                $datas = array();
                $retour['infoRatt'] = $this->container->getParameter('info');
                
                $fh = fopen($url, 'r');
                
                $id_ligne = 0;
                $listeDonneesAMettreAJour = array();
                $listeUaisInsee = array();
                $listeUaisInseeCommunes = array();
                $listeUaisPrioritaires = array();
                $listeDonneesPrioritaireAMettreAJour = array();
                
                while ($ligne = utf8_encode(fgets($fh))) {
                    
                    $id_ligne ++;
                    
                    // Récupération des données de rattachement
                    $datas = explode($delimiter, $ligne);
                    
                    // On ne traite que les lignes dont le TYPECO = L (lien UAI) et VALECO = 10
                    if ($datas[0] == 'L' && $datas[3] == 10) {
                        $uai = $datas[2];
                        $ident = $datas[4];
                        
                        if (null == $uai) {
                          	$logger->error($id_ligne . " : UAIRNE est vide");
                        } elseif (null == $ident) {
                          	$logger->error($id_ligne . " : IDENT est vide");
                        } else {
                            $array = array();
                            $array['uai'] = $uai;
                            $array['insee'] = $ident;
                            array_push($listeUaisInsee, $array);
                        }
                    } // 0167949 traitement des lignes du code prioritaire 35 ou  36 REP/REP+ et le champ RNE n'est pas vide et le typeeco est à L
                    else if ($datas[0] == 'L' && !empty($datas[2])) {
						if (array_key_exists($datas[2], $arrayRefEtablissement)) { // on verifie si UAI de l'etablissement existe dans la BD
							if (!empty($datas[3]) && ($datas[3] == '35' || $datas[3] == '36')) { // on traite que les types prio 35 et 36
								$array = array();
								$array['uai'] = $arrayRefEtablissement[$datas[2]];
								$array['typePrioritaire'] = $datas[3];
								array_push($listeUaisPrioritaires, $array);
							} else {
								$logger->error($id_ligne . " : le code ne correspont pas ni à REP ni à REPPLUS.");
							}
                        } else {
                    		$logger->error($id_ligne . " : l'UAI n'existe pas en base.");
                    	}
                	}
                }
                fclose($fh);
                
                // Récupération des identifiants commune
                if (sizeof($listeUaisInsee) > 0) {
                	$idCommunes = $this->em->getRepository(RefCommune::class)->findIdParCodeInsee($listeUaisInsee);
                	foreach ($listeUaisInsee as $key => $array) {
                		$tmp = $array;
                		if (array_key_exists($array['insee'], $idCommunes)) {
                			$tmp['id_commune'] = $idCommunes[$array['insee']];
                		} else {
                			$logger->error("La commune pour le code insee " . $array['insee'] . " n'a pas été trouvée");
                			$retour['nbCommunesNonTrouvees'] ++;
                			$tmp['id_commune'] = null;
                		}

                		array_push($listeUaisInseeCommunes, $tmp);
                	}

                }

               	$logger->info("Avant purge : " . sizeof($listeUaisInseeCommunes) . " lignes à traiter");
                
           
                for ($key = 0; $key < sizeof($listeUaisInseeCommunes); $key ++) {
                    
                    $array = $listeUaisInseeCommunes[$key];
                    
                    if (array_key_exists($array['uai'], $arrayRefEtablissementCommune) && ($arrayRefEtablissementCommune[$array['uai']] != $array['id_commune'] || null == $arrayRefEtablissementCommune[$array['uai']])) {
                        // On doit mettre à jour la donnée
                        array_push($listeDonneesAMettreAJour, $listeUaisInseeCommunes[$key]);
                        $retour['nbEtabUpdate'] ++;
                    }
                }
                
            	$logger->info("Après purge : " . sizeof($listeDonneesAMettreAJour) . " lignes à traiter");
                
            	// purge des types prioritaires
            	for ($key = 0; $key < sizeof($listeUaisPrioritaires); $key ++) {
            		$array = $listeUaisPrioritaires[$key];
            		if (array_key_exists($array['uai'], $arrayRefEtablissementTypePrioritaire) && $arrayRefEtablissementTypePrioritaire[$array['uai']] != $array['typePrioritaire']) {
            			// On doit mettre à jour la donnée
            			array_push($listeDonneesPrioritaireAMettreAJour, $listeUaisPrioritaires[$key]);
            		}
            	}
            	
                // Mise à jour de la base
                if (sizeof($listeDonneesAMettreAJour) > 0) {
                    $this->em->getRepository(RefEtablissement::class)->updateListeRefEtablissementCommuneByRamsese($listeDonneesAMettreAJour);
                }

                // Mise à jour des types prioritaire dans la base
                if (sizeof($listeUaisPrioritaires) > 0) {
                	$this->em->getRepository(RefEtablissement::class)->updateListeRefEtablissementTypePrioritaireByRamsese($listeUaisPrioritaires);
                }
				
                // compter les etablissement dont le type prioritaire est mis à jour
                if (sizeof($listeDonneesAMettreAJour) > 0) {
                	foreach ($listeDonneesPrioritaireAMettreAJour as $keyPrio => $arrayPrio) {
                		$uaiExist = false;
                		foreach ($listeDonneesAMettreAJour as $key => $array) {
                			if ($array['uai'] == $arrayPrio['uai']) {
                				$uaiExist = true;
                				break;
                			}
                		}
                		if (!$uaiExist) 
                			$retour['nbEtabUpdate'] ++;
                	}
                } else {
                	$retour['nbEtabUpdate'] += sizeof($listeDonneesPrioritaireAMettreAJour);
                }
				
                // déplacer le fichier traité
                $fichierArchive = $this->getRootDir() . $traiteDir . date("YmdHi") . "_" . basename($url);
                rename($url, $fichierArchive);
                
                // resultat du traitement
            	$logger->info("Résultat du traitement : " . $retour['nbEtabUpdate'] . " établissements traité(s) et " . $retour['nbCommunesNonTrouvees'] . " établissements n'ont pas été mis à jour suite à un code insee introuvable.");
            } else {
                $erreur = "Le format du fichier n'est pas pris en charge par l'application : " . basename($url);
              	$logger->error($erreur);
                $retour['erreur'] = $erreur;
            }
       	$logger->info("Fin import fichier");
        return $retour;
    }
    
   

    protected function getRootDir()
    {
        return __DIR__ . "/../../../../web/";
    }
}