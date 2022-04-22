<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EleResultatDetail
 *
 * @ORM\Table(name="ele_resultat_detail", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\EleResultatDetailRepository")
 */
class EleResultatDetail
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
     * @ORM\Column(name="libelle", type="string", length=255)
     */
    private $libelle;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_voix", type="integer")
     */
    private $nbVoix;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_sieges", type="integer")
     */
    private $nbSieges;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_sieges_sort", type="integer")
     */
    private $nbSiegesSort;

    /**
     *
     * 		@var EleEtablissement
     * 		@ORM\ManyToOne(targetEntity="App\Entity\EleEtablissement", cascade={"persist"})
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set libelle
     *
     * @param string $libelle
     * @return EleResultatDetail
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * Get libelle
     *
     * @return string
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * Set nbVoix
     *
     * @param integer $nbVoix
     * @return EleResultatDetail
     */
    public function setNbVoix($nbVoix)
    {
        $this->nbVoix = $nbVoix;

        return $this;
    }

    /**
     * Get nbVoix
     *
     * @return integer
     */
    public function getNbVoix()
    {
        return $this->nbVoix;
    }

    /**
     * Set nbSieges
     *
     * @param integer $nbSieges
     * @return EleResultatDetail
     */
    public function setNbSieges($nbSieges)
    {
        $this->nbSieges = $nbSieges;

        return $this;
    }

    /**
     * Get nbSieges
     *
     * @return integer
     */
    public function getNbSieges()
    {
        return $this->nbSieges;
    }

    /**
     * Set nbSiegesSort
     *
     * @param integer $nbSiegesSort
     * @return EleResultatDetail
     */
    public function setNbSiegesSort($nbSiegesSort)
    {
        $this->nbSiegesSort = $nbSiegesSort;

        return $this;
    }

    /**
     * Get nbSiegesSort
     *
     * @return integer
     */
    public function getNbSiegesSort()
    {
        return $this->nbSiegesSort;
    }

    /**
     * Set electionEtab
     *
     * @param EleEtablissement $electionEtab
     */
    public function setElectionEtab( EleEtablissement $electionEtab){
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
    public function setOrganisation( RefOrganisation $organisation){
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
