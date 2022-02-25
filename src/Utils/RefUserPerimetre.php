<?php

namespace App\Utils;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\RefUser;
use App\Entity\RefProfil;
use App\Entity\RefTypeElection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * App\Utils\RefUserPerimetre
 */
class RefUserPerimetre {

    private $doctrine;
    private $container; //Service container

    /**
     * @var array() $typeElections
     */
    private $typeElections;

    /**
     * @var array() $degres
     */
    private $degres;

    /**
     * @var array() academies
     */
    private $academies;

    /**
     * @var array() departements
     */
    private $departements;

    /**
     * @var array() communes
     * // TODO NON UTILISE
     */
    private $communes;

    /**
     * @var array() $etablissements
     */
    private $etablissements;

    /**
     *
     * @var unknown
     */
    private $limitedToEtabs;

    private $isPerimetreVide;

    public function __construct(ManagerRegistry $doctrine, ContainerInterface $container) {
        $this->doctrine = $doctrine;
        $this->container = $container;
        $this->typeElections = array();
        $this->academies = array();
        $this->departements = array();
        $this->communes = array();
        $this->etablissements = array();
        $this->degres = array();
        $this->limitedToEtabs = false;
        $this->isPerimetreVide = false;
    }

    /**
     * Fills Perimetre with User specificities
     * @param RefUser $user
     * @param $lstUai = liste des uais des établissements faisant partie du périmètre de l'utilisateur
     * @return RefUserPerimetre (self)
     */
    public function setPerimetreForUser(RefUser $user, $lst_uai = array(), $type_elec = array(), $lst_numero_departement = array()) {
        !        $typeElectionParents = $this->doctrine->getRepository('EPLEElectionBundle:RefTypeElection')->find(RefTypeElection::ID_TYP_ELECT_PARENT);
        $typesElections = array();
        $campagne = $this->doctrine->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne(RefTypeElection::ID_TYP_ELECT_PARENT);

        // Restriction périmètre utilisateur
        if (!empty($type_elec)) {
            foreach ($type_elec as $id) {
                array_push($typesElections, $this->doctrine->getRepository('EPLEElectionBundle:RefTypeElection')->find($id));
            }
        } else {
            $typesElections = $this->doctrine->getRepository('EPLEElectionBundle:RefTypeElection')->findAll();
        }

        switch ($user->getProfil()->getCode()) {

            case RefProfil::CODE_PROFIL_DGESCO:
                $this->setDegres(array('1', '2'));
                $this->setTypeElections($typesElections);
                //Charger The active academy in the current Year:$this->setAcademies($this->doctrine->getRepository('EPLEElectionBundle:RefAcademie')->findAll());
                $this->setAcademies($this->doctrine->getRepository('EPLEElectionBundle:RefAcademie')->listeActiveAcademies($campagne));
                break;

            case RefProfil::CODE_PROFIL_RECT:

//                $this->setDegres(array('1', '2'));
//                $this->setTypeElections($typesElections);
//                $academie = $this->doctrine->getRepository('EPLEElectionBundle:RefAcademie')->find($user->getLogin());
//                $this->addAcademie($academie);
//                $departements = $this->doctrine->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('academie' => $academie->getCode()));
//                $this->setDepartements($departements);


//                 v3:
                $this->setDegres(array('1', '2'));
                $this->setTypeElections($typesElections);
                $academie = $this->doctrine->getRepository('EPLEElectionBundle:RefAcademie')->find($user->getLogin());
                $this->addAcademie($academie);
                $checkChildAcad = $this->doctrine->getRepository('EPLEElectionBundle:RefAcademie')->countchildAcademies($academie->getCode());
                $getYearDisactivationAcad =  $academie->getDateDesactivation();
                $dateCampagneDebut = new Datetime($campagne->getAnneeDebut() . '-01-01');
                if($getYearDisactivationAcad  <= $dateCampagneDebut){
                    if (null != $academie->getAcademieFusion()){
                        $departements = $this->doctrine->getRepository('EPLEElectionBundle:RefDepartement')->findBydepartementAdademiefusionner( $academie->getAcademieFusion()->getCode());
                        $newAcademies = $this->doctrine->getRepository('EPLEElectionBundle:RefAcademie')->getchildnewAcademies( $academie->getAcademieFusion()->getCode());
                        $this->setAcademies(   $newAcademies);
                        $this->setDepartements($departements);
                        $user->setIdZone($academie->getAcademieFusion()->getCode());
                    }
                }elseif($checkChildAcad > 0){
                    $departements = $this->doctrine->getRepository('EPLEElectionBundle:RefDepartement')->findBydepartementAdademiefusionner( $academie->getCode());
                    $newAcademies = $this->doctrine->getRepository('EPLEElectionBundle:RefAcademie')->getchildnewAcademies( $academie->getCode());
                    $this->setAcademies( $newAcademies);
                    $this->setDepartements($departements);

                }else{
                    $departements = $this->doctrine->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('academie' => $academie->getCode()));
                    $this->setDepartements($departements);
                    // $this->addAcademie($academie);
                }
                break;

            case RefProfil::CODE_PROFIL_DSDEN:
                $this->setDegres(array('1', '2'));
                $this->setTypeElections($typesElections);
                if ($lst_numero_departement != null) {
                    foreach ($lst_numero_departement as $numero_departement) {
                        $departement = $this->doctrine->getRepository('EPLEElectionBundle:RefDepartement')->find($numero_departement);
                        if ($departement != null) {
                            $this->addDepartement($departement);
                            $academie = $departement->getAcademie();
                            $dateCampagneDebut = new Datetime($campagne->getAnneeDebut() . '-01-01');
                            if($academie->getAcademieFusion() != null && $academie->getDateDesactivation() <= $dateCampagneDebut) {
                                $academie = $this->doctrine->getRepository('EPLEElectionBundle:RefAcademie')->find( $academie->getAcademieFusion()->getCode());
                            }
                            $this->addAcademie($academie);
                            $user->setIdZone($academie->getCode());
                        }
                    }
                }
                break;

            case RefProfil::CODE_PROFIL_IEN:
                $this->setDegres(array('1'));
                $this->setTypeElections($typesElections);
                foreach ($lst_uai as $uai) {
                    $etab = $this->doctrine->getRepository('EPLEElectionBundle:RefEtablissement')->findOneByUai($uai);
                    if (null != $etab && null != $etab->getCommune() && $etab->getActif()){ // YME - HPQC DEFECT #220
                        $this->addEtablissement($etab);
                        $this->addCommune($etab->getCommune());
                        $this->addDepartement($etab->getCommune()->getDepartement());
                        $academie = $etab->getCommune()->getDepartement()->getAcademie();
                        $dateCampagneDebut = new Datetime($campagne->getAnneeDebut() . '-01-01');
                        if($academie->getAcademieFusion() != null && $academie->getDateDesactivation() <= $dateCampagneDebut) {
                            $academie = $this->doctrine->getRepository('EPLEElectionBundle:RefAcademie')->find( $academie->getAcademieFusion()->getCode());
                            //$newAcademies = $this->doctrine->getRepository('EPLEElectionBundle:RefAcademie')->getchildnewAcademies( $academie->getAcademieFusion()->getCode());
                            //$this->setAcademies( $newAcademies);
                        }
                        $this->addAcademie($academie);
                        $user->setIdZone($academie->getCode());
                        //$this->addAcademie($etab->getCommune()->getDepartement()->getAcademie());
                    }
                }
                $this->limitedToEtabs = true;
                break;

            case RefProfil::CODE_PROFIL_CE:
                $this->setDegres(array('2'));
                $this->setTypeElections($typesElections);
                foreach ($lst_uai as $uai) {
                    $etab = $this->doctrine->getRepository('EPLEElectionBundle:RefEtablissement')->findOneByUai($uai);
                    if (null != $etab && null != $etab->getCommune() && $etab->getActif()){ // YME - HPQC DEFECT #220
                        $this->addEtablissement($etab);
                        $this->addCommune($etab->getCommune());
                        $this->addDepartement($etab->getCommune()->getDepartement());
                        $academie = $etab->getCommune()->getDepartement()->getAcademie();
                        $dateCampagneDebut = new Datetime($campagne->getAnneeDebut() . '-01-01');
                        if($academie->getAcademieFusion() != null && $academie->getDateDesactivation() <= $dateCampagneDebut) {
                            $academie = $this->doctrine->getRepository('EPLEElectionBundle:RefAcademie')->find( $academie->getAcademieFusion()->getCode());
                        }
                        $user->setIdZone($academie->getCode());
                        $this->addAcademie($academie);
                    }
                }
                $this->limitedToEtabs = true;
                break;

            case RefProfil::CODE_PROFIL_DE:
                $this->setDegres(array('1'));
                $this->setTypeElections($typesElections);
                foreach ($lst_uai as $uai) {
                    $etab = $this->doctrine->getRepository('EPLEElectionBundle:RefEtablissement')->findOneByUai($uai);
                    if (null != $etab && null != $etab->getCommune()  && $etab->getActif()){ // YME - HPQC DEFECT #220
                        $this->addEtablissement($etab);
                        $this->addCommune($etab->getCommune());
                        $this->addDepartement($etab->getCommune()->getDepartement());
                        $academie = $etab->getCommune()->getDepartement()->getAcademie();
                        $dateCampagneDebut = new Datetime($campagne->getAnneeDebut() . '-01-01');
                        if($academie->getAcademieFusion() != null && $academie->getDateDesactivation() <= $dateCampagneDebut) {
                            $academie = $this->doctrine->getRepository('EPLEElectionBundle:RefAcademie')->find( $academie->getAcademieFusion()->getCode());
                        }
                        $this->addAcademie($academie);
                        $user->setIdZone($academie->getCode());
                    }
                }
                $this->limitedToEtabs = true;
                break;

            case RefProfil::CODE_PROFIL_PARENTS:
                $this->setDegres(array('1', '2'));
                $this->addTypeElection($typeElectionParents);
                break;

            default:
                break;
        }

        if (empty($this->academies)) {
            $this->isPerimetreVide = true;
        } else {
            $this->isPerimetreVide = false;
        }

        return $this;
    }

    public function addAcademie(\App\Entity\RefAcademie $aca)
    {
        if (!in_array($aca, $this->academies)) $this->academies[] = $aca;
    }

    public function addDepartement(\App\Entity\RefDepartement $dept)
    {
        if (!in_array($dept, $this->departements)) $this->departements[] = $dept;
    }

    public function addEtablissement(\App\Entity\RefEtablissement $etab)
    {
        $this->etablissements[] = $etab;
    }

    public function addCommune(\App\Entity\RefCommune $comm)
    {
        if (!in_array($comm, $this->communes)) $this->communes[] = $comm;
    }

    public function addTypeElection(\App\Entity\RefTypeElection $type)
    {
        $this->typeElections[] = $type;
    }

    public function hasElectionsASS_ATE() {
        $typeElection = $this->doctrine->getRepository('EPLEElectionBundle:RefTypeElection')->find(RefTypeElection::ID_TYP_ELECT_ASS_ATE);
        return in_array($typeElection, $this->typeElections);
    }

    public function hasElectionsPEE() {
        $typeElection = $this->doctrine->getRepository('EPLEElectionBundle:RefTypeElection')->find(RefTypeElection::ID_TYP_ELECT_PEE);
        return in_array($typeElection, $this->typeElections);
    }

    public function hasElectionsPARENTS() {
        $typeElection = $this->doctrine->getRepository('EPLEElectionBundle:RefTypeElection')->find(RefTypeElection::ID_TYP_ELECT_PARENT);
        return in_array($typeElection, $this->typeElections);
    }

    public function getTypeElections() {
        return $this->typeElections;
    }

    public function setTypeElections($typeElections) {
        $this->typeElections = $typeElections;
    }

    public function getEtablissements() {
        return $this->etablissements;
    }

    public function setEtablissements($etablissements) {
        $this->etablissements = $etablissements;
    }

    public function getDegres() {
        return $this->degres;
    }

    public function setDegres($degres) {
        $this->degres = $degres;
    }

    public function getAcademies() {
        return $this->academies;
    }

    public function setAcademies($academies)
    {
        $this->academies = $academies;
    }

    public function getDepartements() {
        return $this->departements;
    }

    public function setDepartements($departements) {
        $this->departements = $departements;
    }

    public function getCommunes() {
        return $this->communes;
    }

    public function setCommunes($communes) {
        $this->communes = $communes;
    }

    public function getLimitedToEtabs()
    {
        return $this->limitedToEtabs;
    }

    public function setLimitedToEtabs($limitedToEtabs)
    {
        $this->limitedToEtabs = $limitedToEtabs;
        return $this;
    }

    public function getIsPerimetreVide(){
        return $this->isPerimetreVide;
    }
    public function setIsPerimetreVide($isPerimetreVide){
        $this->isPerimetreVide = $isPerimetreVide;
        return $this;
    }

    public function getEmailContact($user) {
        $email = $this->container->getParameter('mailer_admin');

        switch ($user->getProfil()->getCode()) {

            /*
            case RefProfil::CODE_PROFIL_RECT:
                // contact académique
                if (!empty($this->academies)) {
                    $academie = $this->academies[0];
                    if ($academie != null) {
                        $contactsAcademie = $this->doctrine->getRepository('EPLEElectionBundle:RefContact')->findContactsByIdZone($academie->getCode());
                        if ($contactsAcademie != null && sizeof($contactsAcademie) > 0) {
                            $contactAcademie = $contactsAcademie[0];
                            $email = $contactAcademie->getEmail1();
                        }
                    }
                }

                break;
            */

            case RefProfil::CODE_PROFIL_IEN:
            case RefProfil::CODE_PROFIL_DE:
                if (!empty($this->departements)) {
                    // 1 seul département -> contact départemental
                    if (sizeof($this->departements) == 1) {
                        $departement = $this->departements[0];
                        if ($departement != null) {
                            $typeElectionParents = $this->doctrine->getRepository('EPLEElectionBundle:RefTypeElection')->find(RefTypeElection::ID_TYP_ELECT_PARENT);
                            $contactsDepartement = $this->doctrine->getRepository('EPLEElectionBundle:RefContact')->findRefContactsByIdZoneTypeElection($departement->getNumero(), $typeElectionParents);
                            if ($contactsDepartement != null && sizeof($contactsDepartement) > 0) {
                                $contactDepartement = $contactsDepartement[0];
                                $email = $contactDepartement->getEmail1();
                            }
                        }

                    }
                }

                break;
            case RefProfil::CODE_PROFIL_CE:
                $email = array();
                if (!empty($this->departements)) {
                    // 1 seul département -> contact départemental
                    if (sizeof($this->departements) == 1) {
                        $departement = $this->departements[0];
                        if ($departement != null) {
                            $contactsDepartement = $this->doctrine->getRepository('EPLEElectionBundle:RefContact')->findContactsByIdZone($departement->getNumero());
                            foreach ($contactsDepartement as $contactDepartement) {
                                if($contactDepartement->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PARENT){
                                    $email[] = $contactDepartement->getEmail2();

                                }else {
                                    $email[] = $contactDepartement->getEmail1();
                                }
                            }
                        }
                    }
                }
                $email = implode(";", array_unique($email));
                break;
            case RefProfil::CODE_PROFIL_DSDEN:
                $email = array();
                if (!empty($this->academies)) {
                    // contact académique
                    if (!empty($this->academies)) {
                        $academie = $this->academies[0];
                        if ($academie != null) {
                            $contactsAcademie = $this->doctrine->getRepository('EPLEElectionBundle:RefContact')->findContactsByIdZone($academie->getCode());
                            foreach ($contactsAcademie as $contactAcademie) {
                                $email[] = $contactAcademie->getEmail1();
                            }
                        }
                    }
                }

                $email = implode(";", array_unique($email));
                break;
            default:
                break;
        }

        if (empty($email)) {
            $email = $this->container->getParameter('mailer_admin');
        }

        return $email;
    }

    public function getUrlEduscol() {
        return $this->container->getParameter('url_eduscol');
    }

    public function getVersion() {
        return $this->container->getParameter('version');
    }

    public function isLimitedToEtabs(){
        return $this->limitedToEtabs;
    }

    public function getUrlDocumentation() {
        return $this->container->getParameter('url_documentation');
    }

    public function getUrlCE()
    {
        return $this->container->getParameter('url_documentation_ce');
    }

    public function getUrlDE()
    {
        return $this->container->getParameter('url_documentation_de');
    }

    public function getRgaaStatus() {
        return $this->container->getParameter('rgaa_status');
    }

    public function getRgaaDeclarationLink() {
        return $this->container->getParameter('rgaa_declaration_link');
    }
}
