<?php
namespace App\Controller;

use App\Entity\RefProfil;
use App\Entity\EleCampagne;
use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefTypeElection;
use App\Entity\EleEtablissement;
use App\Entity\RefEtablissement;
use App\Entity\RefTypeEtablissement;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\RecapitulatifParticipationEtabType;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Model\RecapitulatifParticipationEtabTypeModel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RecapitulatifParticipationController extends AbstractController
{
    private $request;
    private $doctrine;

    public function __construct(RequestStack $request, ManagerRegistry $doctrine) {
        $this->request = $request->getCurrentRequest();
        $this->doctrine = $doctrine;
    }

    public function indexAction($codeUrlTypeElect)
    {
        // reset session
        $this->request->getSession()->set('recap_part_campagne_annee_deb', null);
        $this->request->getSession()->set('recap_part_niveau', 'departement');
        $this->request->getSession()->set('recap_part_type_etab', null);

        if (false === $this->isGranted('ROLE_STATS_TAUX_PART')) {
            throw new AccessDeniedException();
        }
        $user = $this->getUser();
        $params = $this->getParametresStatistiques($codeUrlTypeElect, $user);
        return $this->render('recapitulatifParticipation/indexRecapitulatifParticipation.html.twig', $params);
    }

    private function getParametresStatistiques($codeUrlTypeElect, $user)
    {
        $em = $this->doctrine->getManager();
        $params = array();
        $campagneAnneeDeb = null;
        $niveau = 'departement';
        $typeEtab = null;
        $listEtab = array();
        $codeUrlTypeEtab = 'tous';

        /**
         * **** Récupération du type d'election *****
         */
        $idTypeElect = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $typeElection = $idTypeElect != null ? $em->getRepository(RefTypeElection::class)->find($idTypeElect) : null;
        if (empty($typeElection)) {
            throw $this->createNotFoundException('Le type d\'élection n\'a pas été trouvé.');
        }
        $params['typeElect'] = $typeElection;

        $cze_current = new RecapitulatifParticipationEtabTypeModel($typeElection);

        $form = $this->createForm(RecapitulatifParticipationEtabType::class, $cze_current, ['user'=>$user]);
        if ($this->request->getMethod() == 'POST') {
            $form->handleRequest($this->request);
            if ($form->isSubmitted() && $form->isValid()) {

                $options_recherche = $form->getData(); /* "options_recherche" : un objet "RecapitulatifPaticipationEtabTypeModel",
                                                        * contenant les données de recherche suivantes: 
                										* campagne, niveau, typeEtab, et typeElect */

                $this->request->getSession()->remove('recap_part_campagne_annee_deb');
                $this->request->getSession()->remove('recap_part_niveau');
                $this->request->getSession()->remove('recap_part_type_etab');

                $campagneAnneeDeb = $options_recherche->getCampagne()->getAnneeDebut();
                $niveau = $options_recherche->getNiveau();

                if ($options_recherche->getTypeEtablissement() != null) {
                    $typeEtab = $options_recherche->getTypeEtablissement(); /* Récupération du typeEtab choisi par User */
                } else {
                    $typeEtab = null; /* Sinon, tous */
                }

                // Mise en place des variables en session pour l'export excel
                $this->request->getSession()->set('recap_part_campagne_annee_deb', $campagneAnneeDeb);
                $this->request->getSession()->set('recap_part_niveau', $niveau);
                $this->request->getSession()->set('recap_part_type_etab', null != $typeEtab ? $typeEtab->getId() : null);
            }
        } else {
            $campagneAnneeDeb = $this->request->getSession()->get('recap_part_campagne_annee_deb');
            $niveau = $this->request->getSession()->get('recap_part_niveau');
            if(!empty($this->request->getSession()->get('recap_part_type_etab'))) {
                $typeEtab = $em->getRepository(RefTypeEtablissement::class)->find($this->request->getSession()->get('recap_part_type_etab'));
            } else {
                $typeEtab = null;
            }
        }

        /**
         * **** Récupération de la dernière campagne et de la campagne anterieure *****
         */

        if ($campagneAnneeDeb == null) {
            $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElection);
        } else {
            $listeCampagne = $em->getRepository(EleCampagne::class)->getCampagneParTypeElectionAnneeDebut($typeElection, $campagneAnneeDeb);
            $campagne = (! empty($listeCampagne)) ? $listeCampagne[0] : null;
        }

        if (empty($campagne)) {
            throw $this->createNotFoundException('La campagne pour ce type d\'élection n\'a pas été trouvé.');
        }

        $params['campagne'] = $campagne;
        $listeCampagnePrec = $em->getRepository(EleCampagne::class)->getCampagneParTypeElectionAnneeDebut($typeElection, $campagne->getAnneeDebut() - 1);
        $campagnePrec = (! empty($listeCampagnePrec)) ? $listeCampagnePrec[0] : null;
        $params['campagnePrec'] = $campagnePrec;

        $listParticipation = $em->getRepository(EleEtablissement::class)->findParticipationByNiveauCampagne($campagne->getId(), $campagnePrec->getId(), $niveau, $typeEtab, $user->getPerimetre(), $user, $campagneAnneeDeb);

        $listEtabConso = array();
        $sumInscrits = 0;
        $sumVotants = 0;
        $sumExprimes = 0;
        $sumSiegesPourvoir = 0;
        $sumSiegesPourvus = 0;
        $sumEtabExprimes = 0;
        $sumEtabTotal = 0;
        $cpt = 0;

        if (! empty($listParticipation)) {
            // Col.1 libellé
            // Col.2 nbInscrits campagne en cours
            // Col.3 nbVotants campagne en cours
            // Col.4 nbExprimes campagne en cours
            // Col.5 % suffrages campagne en cours
            // Col.6 % suffrages campagne precedente
            // Col.7 variation % suffrages campagne en cours campagne precedente
            // Col.8 sieges à pourvoir campagne en cours
            // Col.9 sieges pourvus campagne en cours
            // Col.10 % sieges campagne en cours
            // Col.11 % sieges campagne précédente
            // Col.12 variation % sieges campagne en cours campagne précédente
            // Col.13 nb etabs exprimés campagne en cours
            // Col.14 nb etabs total campagne en cours

            $zoneCourante = null;
            foreach ($listParticipation as $line) {
                // Mantis 0123179 récupération du nombre total d'établissements
                $zone = null;
                if ($niveau == 'departement') {
                    $zone = $em->getRepository(RefDepartement::class)->find($line['id']);
                } else {
                    // niveau = academie
                    $zone = $em->getRepository(RefAcademie::class)->find($line['id']);
                }

                //Anomalie 0220201
                // on utilise plus $sumEtabList
                // nombre total des établissements selon la zone, type d'elec ,type d'étab
                $nbEtabTotal = $em->getRepository(RefEtablissement::class)->getNbEtabParTypeEtablissementZoneTypeElection($typeEtab, $zone, $typeElection);

                if ($zoneCourante != $line['libelle']) {
                    // Nouvelle zone
                    if ($line['idCampagne'] == $campagnePrec->getId()) {
                        // Seule la campagne précédente existe
                        // Il n'y a pas de données pour la campagne en cours
                        $listEtabConso[$line['libelle']]['inscrits'] = '-';
                        $listEtabConso[$line['libelle']]['votants'] = '-';
                        $listEtabConso[$line['libelle']]['exprimes'] = '-';
                        $listEtabConso[$line['libelle']]['p1'] = '-';
                        $listEtabConso[$line['libelle']]['siegesPourvoir'] = '-';
                        $listEtabConso[$line['libelle']]['siegesPourvus'] = '-';
                        $listEtabConso[$line['libelle']]['p2'] = '-';
                        $listEtabConso[$line['libelle']]['etabExprimes'] = '-';
                        $listEtabConso[$line['libelle']]['etabTotal'] = '-';
                        $listEtabConso[$line['libelle']]['rappel1'] = round(floatval($line['p1']), 2);
                        $listEtabConso[$line['libelle']]['variation1'] = '-';
                        $listEtabConso[$line['libelle']]['rappel2'] = round(floatval($line['p2']), 2);
                        $listEtabConso[$line['libelle']]['variation2'] = '-';
                    } else {
                        // On inscrit les données pour la campagne en cours
                        $listEtabConso[$line['libelle']]['inscrits'] = $line['sumInscrits'];
                        $listEtabConso[$line['libelle']]['votants'] = $line['sumVotants'];
                        $listEtabConso[$line['libelle']]['exprimes'] = $line['sumExprimes'];
                        $listEtabConso[$line['libelle']]['p1'] = round(floatval($line['p1']), 2);
                        $listEtabConso[$line['libelle']]['siegesPourvoir'] = $line['sumSiegesPourvoir'];
                        $listEtabConso[$line['libelle']]['siegesPourvus'] = $line['sumSiegesPourvus'];
                        $listEtabConso[$line['libelle']]['p2'] = round(floatval($line['p2']), 2);
                        $listEtabConso[$line['libelle']]['etabExprimes'] = $line['sumEtabExprimes'];
                        //Anomalie 0220201
                        $listEtabConso[$line['libelle']]['etabTotal'] = $nbEtabTotal;
                        //$listEtabConso[$line['libelle']]['etabTotal'] = $sumEtabList[$cpt]['sumEtabTotal'];
                        $listEtabConso[$line['libelle']]['rappel1'] = '-';
                        $listEtabConso[$line['libelle']]['variation1'] = '-';
                        $listEtabConso[$line['libelle']]['rappel2'] = '-';
                        $listEtabConso[$line['libelle']]['variation2'] = '-';

                        $sumInscrits += $line['sumInscrits'];
                        $sumVotants += $line['sumVotants'];
                        $sumExprimes += $line['sumExprimes'];
                        $sumSiegesPourvoir += $line['sumSiegesPourvoir'];
                        $sumSiegesPourvus += $line['sumSiegesPourvus'];
                        $sumEtabExprimes += $line['sumEtabExprimes'];
                        $sumEtabTotal += $nbEtabTotal;
                        //$sumEtabTotal += $sumEtabList[$cpt]['sumEtabTotal'];
                        $cpt++;
                    }
                    $zoneCourante = $line['libelle']; // Mise à jour de la zone
                } else {
                    // Même zone : campagne précédente
                    $listEtabConso[$line['libelle']]['rappel1'] = round(floatval($line['p1']), 2);
                    $listEtabConso[$line['libelle']]['variation1'] = round(floatval($listEtabConso[$line['libelle']]['p1'] - $listEtabConso[$line['libelle']]['rappel1']), 2);
                    $listEtabConso[$line['libelle']]['rappel2'] = round(floatval($line['p2']), 2);
                    $listEtabConso[$line['libelle']]['variation2'] = round(floatval($listEtabConso[$line['libelle']]['p2'] - $listEtabConso[$line['libelle']]['rappel2']), 2);
                }
            }
        }

        if ($listEtabConso != null && !empty($listEtabConso)) {
            $listEtabConso['TOTAL']['inscrits'] = $sumInscrits;
            $listEtabConso['TOTAL']['votants'] = $sumVotants;
            $listEtabConso['TOTAL']['exprimes'] = $sumExprimes;
            $listEtabConso['TOTAL']['siegesPourvoir'] = $sumSiegesPourvoir;
            $listEtabConso['TOTAL']['siegesPourvus'] = $sumSiegesPourvus;
            $listEtabConso['TOTAL']['etabExprimes'] = $sumEtabExprimes;
            $listEtabConso['TOTAL']['etabTotal'] = $sumEtabTotal;
            $listEtabConso['TOTAL']['p1'] = '-';
            $listEtabConso['TOTAL']['rappel1'] = '-';
            $listEtabConso['TOTAL']['variation1'] = '-';
            $listEtabConso['TOTAL']['p2'] = '-';
            $listEtabConso['TOTAL']['rappel2'] = '-';
            $listEtabConso['TOTAL']['variation2'] = '-';
        }


        //E18 Reforme territoriale : Gestion des fusions d'academies : merge des données
        if($niveau != 'departement') {
            $academies = array_keys($listEtabConso);
            $newListConso = array();
            $acadHideRappelAnterieur = array();
            $academiesFusionnees = array();
            $isProfilDepartement =
                $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_IEN ||
                $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN ||
                $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DE ||
                $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_CE;
            $campagneDebut = new \DateTime($campagneAnneeDeb . "-01-01");
            foreach ($academies as $academie) {
                if($academie != "TOTAL") {
                    $acadObj = $em->getRepository(RefAcademie::class)->findOneBy(array("libelle" => $academie));
                    if ($acadObj->getDateDesactivation() <= $campagneDebut && $acadObj->getAcademieFusion() != null) {
                        $acadFusion = $em->getRepository(RefAcademie::class)->find($acadObj->getAcademieFusion()->getCode());
                        if (isset($newListConso[$acadFusion->getLibelle()]) && !empty($newListConso[$acadFusion->getLibelle()])) {
                            //Merge des données rattachées à la meme académie de fusion
                            $newListConso[$acadFusion->getLibelle()] = $this->mergeStatParticipation($newListConso[$acadFusion->getLibelle()], $listEtabConso[$acadObj->getLibelle()]);
                        } else {
                            $newListConso[$acadFusion->getLibelle()] = $listEtabConso[$acadObj->getLibelle()];
                        }
                        //Stockage des code des nouvelles académies suite à la fusion (sans doublons)
                        $academiesFusionnees[$acadFusion->getCode()] = $acadFusion->getLibelle();
                        $acadHideRappelAnterieur[$acadFusion->getLibelle()] =  $isProfilDepartement ? false : $acadFusion->getDateActivation()->format('Y') == $campagneAnneeDeb;
                    } else {
                        $newListConso[$acadObj->getLibelle()] = $listEtabConso[$acadObj->getLibelle()];
                        $acadHideRappelAnterieur[$acadObj->getLibelle()] =  $isProfilDepartement ? false : $acadObj->getDateActivation()->format('Y') == $campagneAnneeDeb;
                    }
                }
            }
            if(in_array("TOTAL", $academies)) {
                $totauxEtabTotaux = $listEtabConso["TOTAL"]["etabTotal"];
            }

            //Mise à jour du nombre total d'établissements dans les académies fusionnées (Uniquement utile sur les profil departemantaux)
            if($isProfilDepartement) {
                foreach ($academiesFusionnees as $code => $libelle) {
                    $totalEtab = 0;
                    $totauxEtabTotaux -= $newListConso[$libelle]["etabTotal"]; //Recalcul du total sur la ligne "TOTAL"

                    //Récupération des "enfants" de l'académie fusionné pour faire la somme des établissement
                    $fusionChild = $em->getRepository(RefAcademie::class)->getchildnewAcademies($code);
                    foreach ($fusionChild as $child) {
                        //Pour chaque enfant, on récupère le nombre total d'établissement
                        $tmp = $em->getRepository(RefEtablissement::class)->getNbEtabParTypeEtablissementZoneTypeElection($typeEtab, $child, $typeElection);
                        $totalEtab += $tmp;
                    }
                    $newListConso[$libelle]["etabTotal"] = $totalEtab;
                    $totauxEtabTotaux += $totalEtab;
                }
            }
            uksort($newListConso, 'strnatcasecmp');
            if(in_array("TOTAL", $academies)) {
                $newListConso["TOTAL"] = $listEtabConso["TOTAL"];
                $newListConso["TOTAL"]["etabTotal"] = $totauxEtabTotaux;
            }
            $listEtabConso = $newListConso;
            $params["acadHideRappelAnterieur"] = $acadHideRappelAnterieur;
        }

        foreach ($listEtabConso as $key => $etabConso) {
                if (is_float($etabConso['p1']))
                    $etabConso['p1'] .= ' %';
                if (is_float($etabConso['p2']))
                    $etabConso['p2'] .= ' %';
                if (is_float($etabConso['rappel1']))
                    $etabConso['rappel1'] .= ' %';
                if (is_float($etabConso['rappel2']))
                    $etabConso['rappel2'] .= ' %';
            $listEtabConso[$key] = $etabConso;
        }

        $params['codeUrlTypeEtab'] = $codeUrlTypeEtab;
        $params['campagneAnneeDeb'] = $campagneAnneeDeb;
        $params['listEtabConso'] = $listEtabConso;
        $params['niveau'] = $niveau;
        $params['typeEtab'] = $typeEtab;
        $params['form'] = $form->createView();
        $params['warning'] = $this->getParameter('warning');

        return $params;
    }

    /**
     *
     * @param $codeUrlTypeElect
     */
    public function exportXLSAction($codeUrlTypeElect)
    {
        $user = $this->getUser();
        $params = $this->getParametresStatistiques($codeUrlTypeElect, $user);

        // Récupération des paramètres
        $typeElection = $params['typeElect'];
        $niveau = $params['niveau'];
        $campagne = $params['campagne'];
        $typeEtab = $params['typeEtab'];
        $listEtabConso = $params['listEtabConso'];

        // Génération du fichier Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $styleArray = array(
            'font' => array(
                'bold' => true
            )
        );
        $styleArrayTitre = array(
            'font' => array(
                'color' => array(
                    'rgb' => 'CC33CC'
                )
            )
        );

        // Création du titre
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A1', $typeElection->getLibelle() . ' - Statistiques de participation par ' . $niveau);

        // Récapitulatif de la recherche
        // Campagne
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A3', 'Campagne');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('B3', $campagne->getAnneeDebut() . '-' . $campagne->getAnneeFin());

        // Type d'établissement
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A4', 'Type d\'établissement');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('B4', null != $typeEtab ? $typeEtab->getLibelle() : 'tous');

        $ligneEnteteTableau = 6;
        // Degré
        if ($typeEtab != null) {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A5', 'Degré');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B5', $typeEtab->getDegre());
            $spreadsheet->setActiveSheetIndex(0)->getStyle('A5')->applyFromArray($styleArray);
            $spreadsheet->setActiveSheetIndex(0)->getStyle('B5')->getAlignment()->setHorizontal('left');
            $ligneEnteteTableau = 7;
        }

        // En-tête du tableau
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligneEnteteTableau, $niveau == 'academie' ? 'Académie' : 'Département');

        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('A4')
            ->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('A' . $ligneEnteteTableau)
            ->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('B4')
            ->getAlignment()
            ->setHorizontal('center');

        $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . $ligneEnteteTableau, 'Inscrits');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . $ligneEnteteTableau, 'Votants');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . $ligneEnteteTableau, 'Exprimés');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('E' . $ligneEnteteTableau, 'Part.');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('F' . $ligneEnteteTableau, "Part.\nprec.");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('G' . $ligneEnteteTableau, 'Variation');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('H' . $ligneEnteteTableau, 'Sièges à pourvoir');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('I' . $ligneEnteteTableau, 'Sièges pourvus');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('J' . $ligneEnteteTableau, '% sièges pourvus');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('K' . $ligneEnteteTableau, '% sièges pourvus prec.');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('L' . $ligneEnteteTableau, 'Variation');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('M' . $ligneEnteteTableau, 'Etabs. Exprimés / Total');

        // On boucle sur les entités
        $ligne = $ligneEnteteTableau + 1;
        foreach ($listEtabConso as $libelle => $zone) {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $libelle);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . $ligne, $zone['inscrits']);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . $ligne, $zone['votants']);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . $ligne, $zone['exprimes']);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('E' . $ligne, $zone['p1']);
            if(isset($params['acadHideRappelAnterieur']) && isset($params['acadHideRappelAnterieur'][$libelle]) && $params['acadHideRappelAnterieur'][$libelle] == true) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('F' . $ligne, "-");
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('G' . $ligne, "-");
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('K' . $ligne, "-");
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('L' . $ligne, "-");
            } else {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('F' . $ligne, $zone['rappel1']);
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('G' . $ligne, $zone['variation1']);
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('K' . $ligne, $zone['rappel2']);
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('L' . $ligne, $zone['variation2']);
            }
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('H' . $ligne, $zone['siegesPourvoir']);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('I' . $ligne, $zone['siegesPourvus']);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('J' . $ligne, $zone['p2']);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('M' . $ligne, $zone['etabExprimes'] . '/' . $zone['etabTotal']);

            if ($libelle == 'TOTAL') {
                $spreadsheet->setActiveSheetIndex(0)
                    ->getStyle('A' . $ligne . ':M' . $ligne)
                    ->applyFromArray($styleArray);
            }

            $ligne ++;
        }

        for ($i = $ligneEnteteTableau; $i <= $ligne; $i ++) {
            $spreadsheet->setActiveSheetIndex(0)
                ->getStyle('B' . $i . ':M' . $i)
                ->getAlignment()
                ->setHorizontal('center');
        }

        // Activer la 1ère feuille
        $spreadsheet->setActiveSheetIndex(0);

        // Mise en forme de la feuille
        $sheet->getColumnDimension('A')->setWidth(35);

        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('A1')
            ->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('A1')
            ->applyFromArray($styleArrayTitre);
        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('A3')
            ->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('A4')
            ->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('B' . $ligneEnteteTableau. ':M' . $ligneEnteteTableau)
            ->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('B' . $ligneEnteteTableau. ':M' . $ligneEnteteTableau)
            ->getAlignment()
            ->setWrapText(true);

        // Création du writer
        $writer = new Xlsx($spreadsheet, 'Excel5');
        // Création du nom du fichier
        $fileName = 'Statistiques_Participation_Elections_' . $codeUrlTypeElect . '_par_' . $niveau . '_' . $campagne->getAnneeDebut() . '-' . $campagne->getAnneeFin();

        // Créer la réponse
        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $fileName . '.xls');
        return $response;
    }

    /**
     * Reforme territoriale: permet de fusionner les données de deux academies qui fusionnent
     * @param $data1
     * @param $data2
     */
    private function mergeStatParticipation($data1, $data2) {
        $mergeResult = array(
            'inscrits'=> 0,
            'votants'=> 0,
            'exprimes'=> 0,
            'siegesPourvoir'=> 0,
            'siegesPourvus'=> 0,
            'etabExprimes'=> 0,
            'etabTotal'=> 0,
            'p1'=> 0,
            'rappel1'=> 0,
            'variation1'=> 0,
            'p2'=> 0,
            'rappel2'=> 0,
            'variation2'=> 0,
        );

        foreach ($data1 as $i => $item) {
            $mergeResult[$i] += intval($item);
        }
        foreach ($data2 as $j => $item2) {
            $mergeResult[$j] += intval($item2);
        }

        //Calcul des Pourcentages et variation
        if($mergeResult['inscrits'] == 0) {
            $mergeResult['p1'] = "-";
        } else {
            $mergeResult['p1'] = round($mergeResult['votants'] / $mergeResult['inscrits'] * 100, 2);
        }

        if($mergeResult['siegesPourvoir'] == 0) {
            $mergeResult['p2'] = "-";
        } else {
            $mergeResult['p2'] = round($mergeResult['siegesPourvus'] / $mergeResult['siegesPourvoir'] * 100, 2);
        }

        foreach ($mergeResult as $key => $value) {
            $mergeResult[$key] = $mergeResult[$key] == 0 ? "-" : $mergeResult[$key];
        }

        $mergeResult['rappel1'] = round($mergeResult['rappel1']/2, 2);
        $mergeResult['variation1'] = $mergeResult['p1'] != "-" ? round($mergeResult['p1'] - $mergeResult['rappel1'], 2) : round(0 - $mergeResult['rappel1'], 2);
        $mergeResult['rappel2'] = round($mergeResult['rappel2']/2, 2);
        $mergeResult['variation2'] = $mergeResult['p2'] != "-" ? round($mergeResult['p2'] - $mergeResult['rappel2'], 2) : round(0 - $mergeResult['rappel2'], 2);

        return $mergeResult;
    }
}
