<?php

namespace App\Entity;

use App\Utils\RefUserPerimetre;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\RefProfil;
use App\Entity\RefTypeElection;

/**
 * RefUser
 *
 * @ORM\Table(name="ref_user", options={"collate"="utf8_general_ci"})
 * @ORM\Entity
 */
class RefUser implements UserInterface, \Serializable, EquatableInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="login", type="string", length=64)
     */
    private $login;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=64)
     */
    private $password;

    /**
     * @var string
     * @ORM\Column(name="id_zone", type="string", length=10, nullable=true)
     */
    private $idZone;

    /**
     * @var RefProfil
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\RefProfil")
     * @ORM\JoinColumn(name="id_profil", referencedColumnName="id", nullable=false)
     */
    private $profil;

    /**
     * @var RefUserPerimetre
     */
    private $perimetre;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set login
     *
     * @param string $login
     * @return RefUser
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return RefUser
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set idZone
     *
     * @param string $idZone
     */
    public function setIdZone($idZone) {
        $this->idZone = $idZone;
    }

    /**
     * Get idZone
     *
     * @return string
     */
    public function getIdZone() {
        return $this->idZone;
    }

    /**
     * Set profil
     *
     * @param RefProfil $profil
     */
    public function setProfil( RefProfil $profil) {
        $this->profil = $profil;
    }

    /**
     * Get profil
     *
     * @return RefProfil
     */
    public function getProfil() {
        return $this->profil;
    }

    /**
     * Get roles
     *
     * @return array of attribute role in profil->getRoles()
     */
    public function getRoles() {
        $rolesSF2 = array();
        if(count($this->profil->getRoles())>0){
            $getRoleOfRefRole = function($r) { return $r->getRole();};
            $rolesSF2 = array_map($getRoleOfRefRole, $this->profil->getRoles()->toArray());
        }
        return $rolesSF2;
    }

    /**
     * Set perimetre
     * @param RefUserPerimetre $perimetre
     */
    public function setPerimetre(RefUserPerimetre $perimetre) {
        $this->perimetre = $perimetre;
    }

    /**
     * Get perimetre
     * @return RefUserPerimetre
     */
    public function getPerimetre() {
        return $this->perimetre;
    }

    /*************************************** LOGIQUE METIER *******************************************/

    /**
     * Get Username
     *
     * @return string
     */
    public function getUsername() {
        return $this->login;
    }

    /**
     * Can this User edit this school ?
     * Depends on ROLES granted and regional scope
     * @param RefEtablissement $etablissement
     * @param EleCampagne $campagne
     * @return boolean
     */
    public function canEditEtab($etablissement, $campagne, $joursCalendaires, $eleEtablissement = null) {

        // Test type election
        if ($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
            $granted = in_array('ROLE_SAISIE_RES_PAR', $this->getRoles());
        } else {
            $granted = in_array('ROLE_SAISIE_RES_PE', $this->getRoles());
        }

        // Test etablissement dans le scope
        if ($granted) {
            $granted = $this->isEtabInScope($etablissement);
        }


        // Un DSDEN peut saisir les resultats de l'election parents d'eleves a la place d'un directeur d'ecole
        // pour une campagne modifiable en periode de validation

        // Un rectorat peut saisir les resultats des elections ASS/ATE et PEE ??? la place d'un chef d'etablissement
        // pour une campagne modifiable en periode de validation
        $listeAcademies = array($etablissement->getCommune()->getDepartement()->getAcademie()); // $this->perimetre->getAcademies()

        if ($granted) {
            $granted = ($campagne->isOpenSaisie($listeAcademies, $joursCalendaires) && $this->profil->getCode() != RefProfil::CODE_PROFIL_DSDEN && $this->profil->getCode() != RefProfil::CODE_PROFIL_RECT

                    && ((($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PEE)
                            && $etablissement->getTypeEtablissement()->getDegre() == "2"
                        ) || ($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PARENT))
                )
                ||
                ($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PARENT
                    && $campagne->isOpenValidation($listeAcademies, $joursCalendaires)
                    && $campagne->isPostEditable()
                    // mantis 147942
                    && ($this->profil->getCode() == RefProfil::CODE_PROFIL_DSDEN || $this->profil->getCode() == RefProfil::CODE_PROFIL_RECT)
                )
                ||
                (
                    ($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_ASS_ATE
                        ||$campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PEE)
                    && $campagne->isOpenValidation($listeAcademies, $joursCalendaires)
                    && $campagne->isPostEditable()
                    // mantis 147942
                    && ($this->profil->getCode() == RefProfil::CODE_PROFIL_DSDEN || $this->profil->getCode() == RefProfil::CODE_PROFIL_RECT)
                    && $etablissement->getTypeEtablissement()->getDegre() == 2
                )
                ||
                (
                    // ??lections RP, 2??me d??gr??, ACA et DSDEN
                    (null != $eleEtablissement) && ($this->canSaisieNouvelleElection($etablissement, $eleEtablissement, $campagne, $joursCalendaires)
                    )
                );
        }

        return $granted;
    }

    /**
     *
     * @param $etablissement
     * @param $campagne
     */
    public function canBypassControleResultatSaisie($etablissement, $campagne, $joursCalendaires){

        // Test etablissement dans le scope
        $granted = $this->isEtabInScope($etablissement);

        // Test type election
        if ($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
            $granted = in_array('ROLE_SAISIE_RES_PAR', $this->getRoles())
                && $campagne->isOpenValidation($this->perimetre->getAcademies(), $joursCalendaires)
                && $campagne->isPostEditable()
                // mantis 147942
                && ($this->profil->getCode() == RefProfil::CODE_PROFIL_DSDEN || $this->profil->getCode() == RefProfil::CODE_PROFIL_RECT);
        } else {
            $granted = in_array('ROLE_SAISIE_RES_PE', $this->getRoles())
                && $campagne->isOpenValidation($this->perimetre->getAcademies(), $joursCalendaires)
                && $campagne->isPostEditable()
                // mantis 147942
                && ($this->profil->getCode() == RefProfil::CODE_PROFIL_DSDEN || $this->profil->getCode() == RefProfil::CODE_PROFIL_RECT)
                && $etablissement->getTypeEtablissement()->getDegre() == 2;
        }
        return $granted;
    }


    /**
     * Can this User consult this school ?
     * Depends on ROLES granted and regional scope
     * @param RefEtablissement $etablissement
     * @param RefTypeElection $typeElection
     * @return boolean
     */
    public function canConsultEtab($etablissement, $typeElection) {
        if ($typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
            $granted = in_array('ROLE_RES_ETAB_PAR', $this->getRoles());
        } else {
            $granted = in_array('ROLE_RES_ETAB_PE', $this->getRoles());
        }

        if ($granted) {
            $granted = $this->isEtabInScope($etablissement);
        }

        return $granted;
    }

    /**
     * Is the school in User scope ?
     * @param RefEtablissement $etablissement
     * @return boolean
     */
    public function isEtabInScope($etablissement) {
        switch ($this->profil->getCode()) {
            case RefProfil::CODE_PROFIL_CE:
            case RefProfil::CODE_PROFIL_DE:
            case RefProfil::CODE_PROFIL_IEN:
                $granted = in_array($etablissement, $this->perimetre->getEtablissements());
                break;

            case RefProfil::CODE_PROFIL_DSDEN:
                $granted = in_array($etablissement->getCommune()->getDepartement(), $this->perimetre->getDepartements());
                break;

            case RefProfil::CODE_PROFIL_RECT:
                $academie = $etablissement->getCommune()->getDepartement()->getAcademie();
                $granted = $academie->getCode() == $this->getIdZone() || $academie->getAcademieFusion()->getCode() == $this->getIdZone();
                break;

            default:
                $granted = true;
                break;
        }

        return $granted;
    }



    /**
     * Is the school in User scope for the search for consult ?
     * @param RefEtablissement $etablissement
     * @return boolean
     */
    public function isEtabInScopeForRechercheUAI($etablissement) {

        switch ($this->profil->getCode()) {

            case RefProfil::CODE_PROFIL_CE:
                $granted = in_array($etablissement, $this->perimetre->getEtablissements());
                break;

            case RefProfil::CODE_PROFIL_DE:
                $granted = in_array($etablissement, $this->perimetre->getEtablissements());
                break;

            case RefProfil::CODE_PROFIL_IEN:
                $granted = in_array($etablissement, $this->perimetre->getEtablissements());
                break;

            case RefProfil::CODE_PROFIL_DSDEN:

                if($etablissement->getCommune() != null){
                    $granted = in_array($etablissement->getCommune()->getDepartement(), $this->perimetre->getDepartements());
                    break;
                }

            case RefProfil::CODE_PROFIL_RECT:
                if($etablissement->getCommune() != null){
                    $granted = in_array($etablissement->getCommune()->getDepartement(), $this->perimetre->getDepartements());
                    break;
                }

            default:
                $granted = true;
                break;
        }

        return $granted;

    }

    /**
     * Can this User consult results ?
     * Depends on ROLES granted
     * @param RefTypeElection $typeElection
     * @return boolean
     */
    public function canConsult($typeElection) {
        if ($typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
            $granted = (in_array('ROLE_RES_ETAB_PAR', $this->getRoles()) || in_array('ROLE_RES_GLO_PAR', $this->getRoles()));
        } else {
            $granted = (in_array('ROLE_RES_ETAB_PE', $this->getRoles()) || in_array('ROLE_RES_GLO_PE', $this->getRoles()));
        }

        return $granted;
    }

    /**
     * Can this User validate results ?
     * Depends on ROLES granted
     * @param RefTypeElection $typeElection
     * @return boolean
     */
    public function canValidate($campagne, $joursCalendaires) {

        $granted = false;

        if ($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
            $granted = in_array('ROLE_VALID_RES_PAR', $this->getRoles());
        } else {
            $granted = in_array('ROLE_VALID_RES_PE', $this->getRoles());
        }

        if($granted){
            $granted = $campagne->isOpenValidation($this->perimetre->getAcademies(), $joursCalendaires);
        }
        return $granted;

    }
    /**
     * Can this User devalidate results ?
     * Depends on ROLES granted
     * @param RefTypeElection $typeElection
     * @return boolean
     */
    public function canDevalidate($campagne, $joursCalendaires) {

        $granted = false;

        if ($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
            $granted = in_array('ROLE_DEVALID_RES_PAR', $this->getRoles());
        } else {
            $granted = in_array('ROLE_DEVALID_RES_PE', $this->getRoles());
        }
        if($granted){
            $granted = $campagne->isOpenValidation($this->perimetre->getAcademies(), $joursCalendaires);
        }
        return $granted;
    }

    /**
     * Can this User mass validate results for eleEtabs ?
     * Depends on ROLES granted
     * @return boolean
     */
    public function canMassValidate() {

        return in_array('ROLE_VALID_RES_PAR', $this->getRoles()) || in_array('ROLE_VALID_RES_PE', $this->getRoles());

    }

    /**
     * Can this User transmit school results for validation ?
     * Depends on ROLES granted and regional scope
     * @param RefEtablissement $etablissement
     * @param RefTypeElection $typeElection
     */
    public function canTransmitResultsEtab($etablissement, $typeElection){

        if ($typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
            $granted = in_array('ROLE_TRANS_RES_PAR', $this->getRoles());
        } else {
            $granted = in_array('ROLE_TRANS_RES_PE', $this->getRoles());
        }

        if($granted){
            $granted = $this->isEtabInScope($etablissement);
        }

        return $granted;
    }

    /**
     * Can this user validate results for a school ?
     * @param RefEtablissement $etablissement
     * @param RefTypeElection $typeElection
     * @return boolean
     */
    public function canValidateEtab($etablissement, $campagne, $joursCalendaires){

        $listeAcademies = array($etablissement->getCommune()->getDepartement()->getAcademie()); // $this->perimetre->getAcademies()
        $granted = false;

        if ($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
            $granted = in_array('ROLE_VALID_RES_PAR', $this->getRoles());
        } else {
            $granted = in_array('ROLE_VALID_RES_PE', $this->getRoles());
        }
        if ($granted){
            $granted = $campagne->isOpenValidation($listeAcademies, $joursCalendaires);
        }

        //Verification du perimetre de validation
        if($granted){
            $granted = $this->isEtabInScope($etablissement);
        }

        return $granted;

    }
    /**
     * Can this user devalidate results for a school ?
     * @param RefEtablissement $etablissement
     * @param RefTypeElection $typeElection
     * @return boolean
     */
    public function canDevalidateEtab($etablissement, $campagne, $joursCalendaires){

        $listeAcademies = array($etablissement->getCommune()->getDepartement()->getAcademie()); // $this->perimetre->getAcademies()
        $granted = false;

        if ($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
            $granted = in_array('ROLE_DEVALID_RES_PAR', $this->getRoles());
        } else {
            $granted = in_array('ROLE_DEVALID_RES_PE', $this->getRoles());
        }
        if($granted){
            $granted = $campagne->isOpenValidation($listeAcademies, $joursCalendaires);
        }
        return $granted;
    }


    /**
     * Can this User get a virgin PV ?
     * @param RefEtablissement $etablissement
     * @return boolean
     */
    public function canGetPVVierge($etablissement){
        return in_array('ROLE_GET_PV_VIERGE', $this->getRoles()) && $this->isEtabInScope($etablissement);
    }

    /**
     * Can this user get a filled PV ?
     * @param RefEtablissement $etablissement
     * @return boolean
     */
    public function canGetPVRempli($etablissement){
        return in_array('ROLE_GET_PV_REMPLI', $this->getRoles()) && $this->isEtabInScope($etablissement);
    }

    /**
     * Can this user get a signed PV ?
     * @param RefEtablissement $etablissement
     * @return boolean
     */
    public function canGetPVSigne($etablissement){
        return in_array('ROLE_GET_PV_SIGNE', $this->getRoles()) && $this->isEtabInScope($etablissement);
    }

    /**
     * Can this user upload a PV ?
     * @param RefEtablissement $etablissement
     * @return boolean
     */
    public function canUploadPVSigne($etablissement){
        return in_array('ROLE_UP_PV_SIGNE', $this->getRoles()) && $this->isEtabInScope($etablissement);
    }


    /**
     * Can this user import schools from Ramsese ?
     * @return boolean
     */
    public function canImportRamsese(){
        return in_array('ROLE_IMPORT_RAMSESE', $this->getRoles());
    }

    /**
     * L'user peut-il saisir le nombre de si??ges par tirage au sort ?
     * @param $etablissement
     * @param $eleEtablissement
     * @param $campagne
     * @param $joursCalendaires
     * @return boolean
     */
    public function canSaisieTirageAuSort($etablissement, $eleEtablissement, $campagne, $joursCalendaires, $joursCalendairesIen) {
        // Test type election et role saisie tirage au sort
        if ($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
            $granted = in_array('ROLE_SAISIE_TS_PAR', $this->getRoles());
        } else {
            $granted = in_array('ROLE_SAISIE_TS_PE', $this->getRoles()); // r??le non attribu?? mais pour anticiper selon ECT
        }

        // 014E la campagne n'est pas ferm??e ou est ferm??e mais on l'a r??ouvert
        if ($granted) {
            $granted = ($campagne->getFermee() != 1 || ($campagne->isOpenValidation($this->perimetre->getAcademies(), $joursCalendaires) && $campagne->getFermee() == 1));
        }

        // Test etablissement dans le scope
        if ($granted) {
            $granted = $this->isEtabInScope($etablissement);
        }

        // P??riode de saisie
        if ($granted) {
            $granted = $campagne->isOpenSaisie($this->perimetre->getAcademies(), $joursCalendaires, null, $joursCalendairesIen);
        }

        // Cas de d??ficit ou carence de candidats
        if ($granted) {
            $granted = ($eleEtablissement->getIndCarence() == 1 || $eleEtablissement->getIndDeficit() == 1);
        }

        // R??sultats transmis
        if ($granted) {
            $granted = ($eleEtablissement->getValidation() == EleEtablissement::ETAT_TRANSMISSION);
        }

        return $granted;
    }

    /**
     * PV tirage au sort accessible aussi pendant la p??riode de validation et au del?? si pas de saisie du tirage au sort effectu??
     * @param $etablissement
     * @param $eleEtablissement
     * @param $campagne
     * @param $joursCalendaires
     * @return boolean
     */
    public function canGetPVTirageAuSortInAndAfterValidation($etablissement, $eleEtablissement, $campagne, $joursCalendaires) {

        $granted = true;
        // Test etablissement dans le scope
        if ($granted) {
            $granted = $this->isEtabInScope($etablissement);
        }

        // Cas de d??ficit ou carence de candidats
        if ($granted) {
            $granted = ($eleEtablissement->getIndCarence() == 1 || $eleEtablissement->getIndDeficit() == 1);
        }

        // R??sultats transmis ou valid??s
        if ($granted) {
            $granted = ($eleEtablissement->getValidation() == EleEtablissement::ETAT_TRANSMISSION || $eleEtablissement->getValidation() == EleEtablissement::ETAT_VALIDATION);
        }

        if ($granted) {
            $granted = ($eleEtablissement->getIndTirageSort() >= 1);
        }

        return $granted;
    }

    /**
     * L'user peut-il saisir une nouvelle election ?
     * @param $etablissement
     * @param $eleEtablissement
     * @param $campagne
     * @param $joursCalendaires
     * @return boolean
     */
    public function canSaisieNouvelleElection($etablissement, $eleEtablissement, $campagne, $joursCalendaires) {
        // rectorat ou dsden
        $granted = ($this->profil->getCode() == RefProfil::CODE_PROFIL_DSDEN || $this->profil->getCode() == RefProfil::CODE_PROFIL_RECT);

        // type election RP
        if ($granted) {
            $granted = ($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PEE);
        }

        // Test etablissement dans le scope
        if ($granted) {
            $granted = $this->isEtabInScope($etablissement);
        }

        // Etablissement du second degr??
        if ($granted) {
            $granted = ($etablissement->getTypeEtablissement()->getDegre() == 2);
        }

        // P??riode de saisie
        if ($granted) {
            $granted = $campagne->isOpenSaisie($this->perimetre->getAcademies(), $joursCalendaires);
        }

        // Cas de d??ficit de candidats
        if ($granted) {
            $granted = ($eleEtablissement->getIndDeficit() == 1);
        }

        // R??sultats transmis
        if ($granted) {
            $granted = ($eleEtablissement->getValidation() == EleEtablissement::ETAT_TRANSMISSION);
        }

        return $granted;
    }

    /**
     * @return ?string
     */
    public function getSalt() {
        return null;
    }

    public function eraseCredentials() { }

    public function equals(UserInterface $user) {
        return ($this->login === $user->getUserIdentifier()) ? true : false;
    }

    public function serialize() {
        return \json_encode(array(
            $this->id,
            $this->login
        ));
    }

    public function unserialize($data) {
        list (
            $this->id,
            $this->login
            ) = \json_decode($data);
    }

    /**
     * @return string
     */
    public function getUserIdentifier() {
        return $this->login;
    }

    public function isEqualTo(UserInterface $user)
    {
        return $this->login == $user->getUserIdentifier();
    }
}
