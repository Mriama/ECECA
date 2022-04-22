<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RefOrganisation
 *
 * @ORM\Table(name="ref_organisation", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\RefOrganisationRepository")
 */
class RefOrganisation
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255)
     */
    protected $libelle;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordre", type="integer")
     */
    protected $ordre;

    /**
     * @var integer
     *
     * @ORM\Column(name="obsolete", type="boolean")
     */
    protected $obsolete;


    /**
     * @var RefTypeElection
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\RefTypeElection")
     * @ORM\JoinColumn(name="id_type_election", referencedColumnName="id", nullable=false)
     */
    protected $typeElection;

    /**
     * @var RefFederation
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\RefFederation")
     * @ORM\JoinColumn(name="id_federation", referencedColumnName="id", nullable=true)
     *
     */
    protected $federation;

    /**
     * @var integer
     *
     * @ORM\Column(name="detaillee", type="boolean")
     */
    protected $detaillee;


    public function __construct(RefTypeElection $typeElection = null) {
        $this->id = 0;
        $this->obsolete = false;
        $this->detaillee = false;
        if($typeElection !=null) { $this->typeElection = $typeElection; }
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
     * Set libelle
     *
     * @param string $libelle
     * @return RefOrganisation
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
     * Set ordre
     *
     * @param integer $ordre
     * @return RefOrganisation
     */
    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get ordre
     *
     * @return integer
     */
    public function getOrdre()
    {
        return $this->ordre;
    }

    /**
     * Set obsolete
     *
     * @param integer $obsolete
     * @return RefOrganisation
     */
    public function setObsolete($obsolete)
    {
        $this->obsolete = $obsolete;

        return $this;
    }

    /**
     * Get obsolete
     *
     * @return integer
     */
    public function getObsolete()
    {
        return $this->obsolete;
    }

    /**
     * Set federation
     *
     * @param RefFederation $federation
     */
    public function setFederation(RefFederation $federation=null) {
        $this->federation = $federation;
    }

    /**
     * Get federation
     *
     * @return RefFederation
     */
    public function getFederation() {
        return $this->federation;
    }

    /**
     * Set typeElection
     *
     * @param RefTypeElection $federation
     */
    public function setTypeElection(RefTypeElection $typeElection=null) {
        $this->typeElection = $typeElection;
    }

    /**
     * Get typeElection
     *
     * @return RefTypeElection
     */
    public function getTypeElection() {
        return $this->typeElection;
    }

    /**
     *
     */
    public function getDetaillee()
    {
        return $this->detaillee;
    }

    /**
     *
     * @param $detaillee
     * @return RefOrganisation
     */
    public function setDetaillee($detaillee)
    {
        $this->detaillee = $detaillee;
        return $this;
    }

    public function __sleep() {

        return array('id', 'libelle');
    }
}
