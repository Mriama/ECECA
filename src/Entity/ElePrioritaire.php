<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ElePrioritaire
 *
 * @ORM\Table(name="ele_prioritaire", options={"collate"="utf8_general_ci"}, uniqueConstraints={@ORM\UniqueConstraint(name="prioritaireUnique", columns={"id_type_prioritaire", "id_participation"})})
 * @ORM\Entity(repositoryClass="App\Repository\ElePrioritaireRepository")
 */
class ElePrioritaire
{

    const TYPE_PRIORITAIRE_ECLAIR = 'ECLAIR';

    /**
     *
     * @var integer @ORM\Column(name="id", type="integer")
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     *
     * 		@var RefTypePrioritaire
     *      @ORM\ManyToOne(targetEntity="App\Entity\RefTypePrioritaire")
     *      @ORM\JoinColumn(name="id_type_prioritaire", referencedColumnName="id")
     */
    private $typePrioritaire;

    /**
     *
     * @var integer @ORM\Column(name="nb_inscrits", type="integer")
     */
    private $nbInscrits;

    /**
     *
     * @var integer @ORM\Column(name="nb_votants", type="integer")
     */
    private $nbVotants;

    /**
     *
     * @var integer @ORM\Column(name="nb_nuls_blancs", type="integer")
     */
    private $nbNulsBlancs;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\EleParticipation")
     * @ORM\JoinColumn(name="id_participation", referencedColumnName="id", onDelete="CASCADE")
     */
    private $participation;

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
     * Set typePrioritaire
     *
     * @param string $typePrioritaire
     * @return ElePrioritaire
     */
    public function setTypePrioritaire($typePrioritaire)
    {
        $this->typePrioritaire = $typePrioritaire;

        return $this;
    }

    /**
     * Get typePrioritaire
     *
     * @return RefTypePrioritaire
     */
    public function getTypePrioritaire()
    {
        return $this->typePrioritaire;
    }

    /**
     * Set nbInscrits
     *
     * @param integer $nbInscrits
     * @return ElePrioritaire
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
     * @return ElePrioritaire
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
     *
     */
    public function getNbNulsBlancs()
    {
        return $this->nbNulsBlancs;
    }

    /**
     *
     * @param $nbNulsBlancs
     * @return ElePrioritaire
     */
    public function setNbNulsBlancs($nbNulsBlancs)
    {
        $this->nbNulsBlancs = $nbNulsBlancs;
        return $this;
    }

    /**
     * Set participation
     *
     * @param EleParticipation $participation
     */
    public function setParticipation(EleParticipation $participation)
    {
        $this->participation = $participation;
    }

    /**
     * Get participation
     *
     * @return EleParticipation
     */
    public function getParticipation()
    {
        return $this->participation;
    }

    /**
     * ******************************************* LOGIQUE METIER ***********************************************
     */

    /**
     * *************** Données Calculées ****************************
     */

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
     * Get taux = (nbExprimes / nbInscrits) * 100
     *
     * @return float
     */
    public function getTaux()
    {
        return (empty($this->nbInscrits) ? 0 : ($this->nbVotants / $this->nbInscrits) * 100);
    }

}
