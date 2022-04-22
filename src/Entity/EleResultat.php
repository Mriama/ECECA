<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EleResultat
 *
 * @ORM\Table(name="ele_resultat", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\EleResultatRepository")
 */
class EleResultat {
    /**
     *
     * @var integer
     *
     * 		@ORM\Column(name="id", type="integer")
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     *
     * @var integer
     * 		@ORM\Column(name="nb_voix", type="integer")
     */
    private $nbVoix;

    /**
     *
     * @var integer
     * 		@ORM\Column(name="nb_sieges", type="integer")
     */
    private $nbSieges;

    /**
     *
     * @var integer
     * 		@ORM\Column(name="nb_sieges_sort", type="integer")
     */
    private $nbSiegesSort;

    /**
     *
     * 		@var EleConsolidation
     * 		@ORM\ManyToOne(targetEntity="App\Entity\EleConsolidation")
     *      @ORM\JoinColumn(name="id_consolidation", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $consolidation;

    /**
     *
     * 		@var EleEtablissement
     * 		@ORM\ManyToOne(targetEntity="App\Entity\EleEtablissement")
     *      @ORM\JoinColumn(name="id_etablissement", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $electionEtab;

    /**
     *
     * 		@var RefOrganisation
     * 		@ORM\ManyToOne(targetEntity="App\Entity\RefOrganisation")
     *      @ORM\JoinColumn(name="id_organisation", referencedColumnName="id")
     */
    private $organisation;

    /**
     *
     * @var integer
     * 		@ORM\Column(name="nb_candidats", type="integer")
     */
    private $nbCandidats;

    /**
     * Constructeur de base
     */
    public function __construct(){
        $this->nbVoix = 0;
        $this->nbSieges = 0;
        $this->nbSiegesSort = 0;
        $this->nbCandidats = 0;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(){
        return $this->id;
    }

    /**
     * Set nbVoix
     *
     * @param integer $nbVoix
     * @return EleResultat
     */
    public function setNbVoix($nbVoix){
        $this->nbVoix = $nbVoix;

        return $this;
    }

    /**
     * Get nbVoix
     *
     * @return integer
     */
    public function getNbVoix(){
        return $this->nbVoix;
    }

    /**
     * Set nbSieges
     *
     * @param integer $nbSieges
     * @return EleResultat
     */
    public function setNbSieges($nbSieges){
        $this->nbSieges = $nbSieges;

        return $this;
    }

    /**
     * Get nbSieges
     *
     * @return integer
     */
    public function getNbSieges(){
        return $this->nbSieges;
    }

    /**
     * Set nbSiegesSort
     *
     * @param integer $nbSiegesSort
     * @return EleResultat
     */
    public function setNbSiegesSort($nbSiegesSort){
        $this->nbSiegesSort = $nbSiegesSort;

        return $this;
    }

    /**
     * Get nbSiegesSort
     *
     * @return integer
     */
    public function getNbSiegesSort(){
        return $this->nbSiegesSort;
    }

    /**
     * Set consolidation
     *
     * @param EleConsolidation $consolidation
     */
    public function setConsolidation(EleConsolidation $consolidation){
        $this->consolidation = $consolidation;
    }

    /**
     * Get consolidation
     *
     * @return EleConsolidation
     */
    public function getConsolidation(){
        return $this->consolidation;
    }

    /**
     * Set electionEtab
     *
     * @param EleEtablissement $electionEtab
     */
    public function setElectionEtab(EleEtablissement $electionEtab){
        $this->electionEtab = $electionEtab;
    }

    /**
     * Get electionEtab
     *
     * @return EleEtablissement
     */
    public function getElectionEtab(){
        return $this->electionEtab;
    }

    /**
     * Set organisation
     *
     * @param RefOrganisation $organisation
     */
    public function setOrganisation(RefOrganisation $organisation){
        $this->organisation = $organisation;
    }

    /**
     * Get organisation
     *
     * @return RefOrganisation
     */
    public function getOrganisation(){
        return $this->organisation;
    }

    public function getNbCandidats(){
        return $this->nbCandidats;
    }
    public function setNbCandidats($nbCandidats){
        $this->nbCandidats = $nbCandidats;
        return $this;
    }

    /**
     * ************************************************ LOGIQUE METIER *********************************************
     */
    /**
     * *************** Données Calculées ****************************
     */

    /**
     * Get nbSiegesTotal = (nbSieges + nbSiegeSort)
     *
     * @return int
     */
    public function getNbSiegesTotal(){
        return (min($this->nbSieges, $this->nbCandidats) + $this->nbSiegesSort);
    }
}
