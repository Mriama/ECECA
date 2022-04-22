<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * EleConsolidation
 *
 * @ORM\Table(name="ele_consolidation", options={"collate"="utf8_general_ci"}, uniqueConstraints={@ORM\UniqueConstraint(name="consolidationUnique", columns={"id_type_etablissement", "id_zone", "id_campagne"})})
 * @ORM\Entity(repositoryClass="App\Repository\EleConsolidationRepository")
 */
class EleConsolidation
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
     * @var string idZone
     *
     * @ORM\Column(name="id_zone", type="string", length=10)
     */
    private $idZone;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_etab_exprimes", type="integer")
     */
    private $nbEtabExprimes;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_etab_total", type="integer")
     */
    private $nbEtabTotal;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\EleCampagne")
     * @ORM\JoinColumn(name="id_campagne", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $campagne;


    /**
     * @ORM\OneToOne(targetEntity="App\Entity\EleParticipation", cascade={"persist"})
     * @ORM\JoinColumn(name="id_participation", referencedColumnName="id", onDelete="CASCADE")
     */
    private $participation;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RefTypeEtablissement")
     * @ORM\JoinColumn(name="id_type_etablissement", referencedColumnName="id", nullable=false)
     */
    private $typeEtablissement;

    /**
     * @var ArrayCollection $resultats
     */
    private $resultats;

    /**
     * Constructeur par défaut
     */
    public function __construct() {
        $this->resultats = new ArrayCollection();
    }

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
     * Set nbEtabExprimes
     *
     * @param integer $nbEtabExprimes
     * @return EleConsolidation
     */
    public function setNbEtabExprimes($nbEtabExprimes)
    {
        $this->nbEtabExprimes = $nbEtabExprimes;

        return $this;
    }

    /**
     * Get nbEtabExprimes
     *
     * @return integer
     */
    public function getNbEtabExprimes()
    {
        return $this->nbEtabExprimes;
    }

    /**
     * Set nbEtabTotal
     *
     * @param integer $nbEtabTotal
     * @return EleConsolidation
     */
    public function setNbEtabTotal($nbEtabTotal)
    {
        $this->nbEtabTotal = $nbEtabTotal;

        return $this;
    }

    /**
     * Get nbEtabTotal
     *
     * @return integer
     */
    public function getNbEtabTotal()
    {
        return $this->nbEtabTotal;
    }

    /**
     * Set campagne
     *
     * @param EleCampagne $campagne
     */
    public function setCampagne(
        EleCampagne $campagne) {
        $this->campagne = $campagne;
    }

    /**
     * Get campagne
     *
     * @return EleCampagne
     */
    public function getCampagne() {
        return $this->campagne;
    }

    /**
     * Set participation
     *
     * @param EleParticipation $participation
     */
    public function setParticipation(
        EleParticipation $participation) {
        $this->participation = $participation;
    }

    /**
     * Get participation
     *
     * @return EleParticipation
     */
    public function getParticipation() {
        return $this->participation;
    }

    /**
     * Set typeEtablissement
     *
     * @param RefTypeEtablissement $typeEtablissement
     */
    public function setTypeEtablissement(
        RefTypeEtablissement $typeEtablissement) {
        $this->typeEtablissement = $typeEtablissement;
    }

    /**
     * Get typeEtablissement
     *
     * @return RefTypeEtablissement
     */
    public function getTypeEtablissement() {
        return $this->typeEtablissement;
    }

    /**
     * Add resultat
     *
     * @param EleResultat $resultat
     */
    public function addResultat(EleResultat $resultat) {
        $this->resultats[] = $resultat;
    }

    /**
     * Remove resultat
     *
     * @param EleResultat $resultat
     */
    public function removeResultat(EleResultat $resultat) {
        $this->resultats->removeElement($resultat);
    }

    /**
     * Get resultats
     *
     * @return Collection
     */
    public function getResultats() {
        return $this->resultats;
    }

    /**
     * Set resultats
     *
     * @param array of \App\Entity\EleResultat $resultats
     */
    public function setResultats($resultats) {
        $this->resultats = $resultats;
    }

    /******************************** LOGIQUE METIER **************************************/

    /***************** Données Calculées *****************************/

    /**
     *
     *
     * @return integer
     */
    public function getPourcentageNbEtabsExprimes() {
        return empty($this->nbEtabTotal)? 0 : ( ($this->nbEtabExprimes / $this->nbEtabTotal) * 100 );
    }

    /***************** Données Calculées *****************************/

    /**
     * Get nbVoixTotal = somme(nbVoix)
     *
     * @return integer
     */
    public function getNbVoixTotal() {
        $nbVoixTotal = 0;
        foreach ($this->resultats as $resultat) {
            $nbVoixTotal = $nbVoixTotal + $resultat->getNbVoix();
        }
        return $nbVoixTotal;
    }

    /**
     * Get nbSiegesTotal = somme(nbSieges)
     *
     * @return integer
     */
    public function getNbSiegesTotal() {
        $nbSiegesTotal = 0;
        foreach ($this->resultats as $resultat) {
            $nbSiegesTotal = $nbSiegesTotal + min($resultat->getNbSieges(), $resultat->getNbCandidats()) + $resultat->getNbSiegesSort();
        }
        return $nbSiegesTotal;
    }

}
