<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * EleParticipation
 *
 * @ORM\Table(name="ele_participation", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\EleParticipationRepository")
 */
class EleParticipation
{

    /**
     *
     * @var integer @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     *
     * @var integer @ORM\Column(name="nb_inscrits", type="integer")
     */
    protected $nbInscrits;

    /**
     *
     * @var integer @ORM\Column(name="nb_votants", type="integer")
     */
    protected $nbVotants;

    /**
     *
     * @var integer @ORM\Column(name="nb_nuls_blancs", type="integer")
     */
    protected $nbNulsBlancs;

    /**
     *
     * @var integer @ORM\Column(name="nb_sieges_pourvoir", type="integer")
     */
    protected $nbSiegesPourvoir;

    /**
     *
     * @var integer @ORM\Column(name="nb_sieges_pourvus", type="integer")
     */
    protected $nbSiegesPourvus;

    /**
     *
     * @var ArrayCollection $detailsPrioritaires (pour les consolidations uniquement)
     */
    protected $detailsPrioritaires;


    /**
     * Non mappé par ORM
     */
    protected $nbExprimes;

    /**
     *
     * @var integer @ORM\Column(name="nb_sieges_sort", type="integer", nullable=true)
     */
    protected $nbSiegesSort;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RefModaliteVote")
     * @ORM\JoinColumn(name="modalite_vote", referencedColumnName="id", nullable=true)
     */
    private $modaliteVote;

    /**** Données consolidés dans l'affichage des particpation d'une zone ****/
    private $consolidationVoteUrneCorrespondance;

    private $consolidationVoteCorrespondance;

    /**
     * Constructeur de base
     */
    public function __construct()
    {
        $this->detailsPrioritaires = new ArrayCollection();
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
     * Set nbInscrits
     *
     * @param integer $nbInscrits
     * @return EleParticipation
     */
    public function setNbInscrits($nbInscrits)
    {
        $this->nbInscrits = $nbInscrits;

        return $this;
    }

    /**
     * Get nbInscrits
     *
     * @return integer
     */
    public function getNbInscrits()
    {
        return $this->nbInscrits;
    }

    /**
     * Set nbVotants
     *
     * @param integer $nbVotants
     * @return EleParticipation
     */
    public function setNbVotants($nbVotants)
    {
        $this->nbVotants = $nbVotants;

        return $this;
    }

    /**
     * Get nbVotants
     *
     * @return integer
     */
    public function getNbVotants()
    {
        return $this->nbVotants;
    }

    /**
     */
    public function getNbNulsBlancs()
    {
        return $this->nbNulsBlancs;
    }

    /**
     *
     * @param $nbNulsBlancs
     * @return EleParticipation
     */
    public function setNbNulsBlancs($nbNulsBlancs)
    {
        $this->nbNulsBlancs = $nbNulsBlancs;
        return $this;
    }

    /**
     * Set nbSiegesPourvoir
     *
     * @param integer $nbSiegesPourvoir
     * @return EleParticipation
     */
    public function setNbSiegesPourvoir($nbSiegesPourvoir)
    {
        $this->nbSiegesPourvoir = $nbSiegesPourvoir;

        return $this;
    }

    /**
     * Get nbSiegesPourvoir
     *
     * @return integer
     */
    public function getNbSiegesPourvoir()
    {
        return $this->nbSiegesPourvoir;
    }

    /**
     * Set nbSiegesPourvus
     *
     * @param integer $nbSiegesPourvus
     * @return EleParticipation
     */
    public function setNbSiegesPourvus($nbSiegesPourvus)
    {
        $this->nbSiegesPourvus = $nbSiegesPourvus;

        return $this;
    }

    /**
     * Get nbSiegesPourvus
     *
     * @return integer
     */
    public function getNbSiegesPourvus()
    {
        return $this->nbSiegesPourvus;
    }

    /**
     * Add detailPrioritaire
     *
     * @param ElePrioritaire $detailPrioritaire
     */
    public function addDetailPrioritaire(ElePrioritaire $detailPrioritaire)
    {
        $this->detailsPrioritaires[] = $detailPrioritaire;
    }

    /**
     * Get detailsPrioritaires
     *
     * @return Collection
     */
    public function getDetailsPrioritaires()
    {
        return $this->detailsPrioritaires;
    }

    /**
     * Set detailsPrioritaires
     *
     * @param
     *            array of \App\Entity\ElePrioritaire $detailsPrioritaires
     */
    public function setDetailsPrioritaires($detailsPrioritaires)
    {
        $this->detailsPrioritaires = $detailsPrioritaires;
    }

    public function getNbSiegesSort() {
        return $this->nbSiegesSort;
    }
    public function setNbSiegesSort($nbSiegesSort) {
        $this->nbSiegesSort = $nbSiegesSort;
        return $this;
    }

    /**
     * Set modaliteVote
     *
     * @param RefModaliteVote $modaliteVote
     */
    public function setModaliteVote($modaliteVote) {
        $this->modaliteVote = $modaliteVote;
    }

    /**
     * Get modaliteVote
     *
     * @return RefModaliteVote
     */
    public function getModaliteVote() {
        return $this->modaliteVote;
    }

    /** CONSOLIDATION MODALITE DE VOTE : UTILISE DANS LES RESULTATS D'UNE ZONE */
    public function setConsolidationVoteUrneCorrespondance($nbVote) {
        return $this->consolidationVoteUrneCorrespondance = $nbVote;
    }

    public function setConsolidationVoteCorrespondance($nbVote) {
        return $this->consolidationVoteCorrespondance = $nbVote;
    }

    public function getConsolidationVoteUrneCorrespondance() {
        return $this->consolidationVoteUrneCorrespondance;
    }

    public function getConsolidationVoteCorrespondance() {
        return $this->consolidationVoteCorrespondance;
    }

    /**
     * *************************************** LOGIQUE METIER ************************************
     */

    /**
     * *************** Données Calculées ****************************
     */

    /**
     *
     * @param $nbExprimes
     * @return EleParticipation
     */
    public function setNbExprimes($nbExprimes)
    {
        $this->nbExprimes = $nbExprimes;
        return $this;
    }

    /**
     * Get nbExprimes = (nbVotants - nbBlancs)
     *
     * @return integer
     */
    public function getNbExprimes()
    {
        return ($this->nbVotants - $this->nbNulsBlancs);
    }

    /**
     * Get taux = (nbVotants / nbInscrits) * 100
     *
     */
    public function getTaux()
    {
        $taux = 0;
        if ($this->nbInscrits != 0)
            $taux = (($this->nbVotants / $this->nbInscrits) * 100);

        return $taux;
    }

    /**
     * Get quotient = (nbExprimes / nbSiegesPourvoir)
     *
     * @return float
     */
    public function getQuotient()
    {
        $quotient = 0;
        if ($this->nbSiegesPourvoir != 0)
            $quotient = round(($this->getNbExprimes() / $this->nbSiegesPourvoir), 2);

        return $quotient;
    }

    /**
     * Get tauxSieges = (nbSiegesPourvus / nbSiegesPourvoir)
     *
     * @return float
     */
    public function getTauxSieges()
    {
        $taux_sieges = 0;
        if ($this->nbSiegesPourvoir != 0)
            $taux_sieges = ($this->nbSiegesPourvus / $this->nbSiegesPourvoir) * 100;

        return $taux_sieges;
    }

    /**
     * Get quotient = ( (Somme des ElePrioritaire.nbExprimes) / nbSiegesPourvoir)
     *
     * @return float
     */
    public function getQuotientDetailsPrioritaires()
    {
        $nbExprimesEclair = 0;
        foreach ($this->detailsPrioritaires as $dp) {
            $nbExprimesEclair += $dp->getNbExprimes();
        }
        return ($nbExprimesEclair / $this->nbSiegesPourvoir);
    }
}
