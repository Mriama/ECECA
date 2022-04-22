<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * RefEtablissement
 *
 * @ORM\Table(name="ref_etablissement", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\RefEtablissementRepository")
 */
class RefEtablissement
{

    /**
     * @var string
     *
     * @ORM\Column(name="uai", type="string", length=8)
     * @ORM\Id
     */
    private $uai;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255)
     */
    private $libelle;

    /**
     * @var string
     *
     * @ORM\Column(name="contact", type="string", length=255)
     */
    private $contact;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\RefTypePrioritaire")
     * @ORM\JoinColumn(name="id_type_prioritaire", referencedColumnName="id")
     */
    private $typePrioritaire;

    /**
     * @var integer
     *
     * @ORM\Column(name="actif", type="boolean")
     */
    private $actif;

    /**
     *
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\RefTypeEtablissement")
     * @ORM\JoinColumn(name="id_type_etablissement", referencedColumnName="id", nullable=false)
     */
    private $typeEtablissement;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RefCommune")
     * @ORM\JoinColumn(name="id_commune", nullable=true, referencedColumnName="id")
     */
    private $commune;

    /**
     * @var ArrayCollection $electionsEtabs
     */
    private $electionsEtabs;


    /**
     * @var \DateTime @ORM\Column(name="date_fermeture", type="date", nullable=true)
     */
    private $date_fermeture;



    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RefZoneNature")
     * @ORM\JoinColumn(name="uai_nature", referencedColumnName="uai_nature")
     */
    private $uai_nature;


    /**
     * Constructeur de base
     * @param string $uai
     */
    public function __construct($uai=null) {
        if (empty($uai)) { $this->uai=0; }
        $this->actif = true;
        $this->electionsEtabs = new ArrayCollection();
    }






    /**
     * Set uai
     *
     * @param string $uai
     * @return RefEtablissement
     */
    public function setUai($uai)
    {
        $this->uai = $uai;

        return $this;
    }

    /**
     * Get uai
     *
     * @return string
     */
    public function getUai()
    {
        return $this->uai;
    }

    /**
     * Set libelle
     *
     * @param string $libelle
     * @return RefEtablissement
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
     * Set contact
     *
     * @param string $contact
     * @return RefEtablissement
     */
    public function setContact($contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return string
     */
    public function getContact()
    {
        return $this->contact;
    }



    /**
     * Set actif
     *
     * @param integer $actif
     * @return RefEtablissement
     */
    public function setActif($actif)
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * Get actif
     *
     * @return integer
     */
    public function getActif()
    {
        return $this->actif;
    }


    /**
     * Set commune
     *
     * @param RefCommune $commune
     */
    public function setCommune(
        RefCommune $commune = null) {
        $this->commune = $commune;
    }

    /**
     * Get commune
     *
     * @return RefCommune
     */
    public function getCommune() {
        return $this->commune;
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
     * Get electionsEtabs
     *
     * @return Collection
     */
    public function getElectionsEtabs() {
        return $this->electionsEtabs;
    }

    /**
     * Set electionsEtabs
     *
     * @param array of \App\Entity\EleEtablissement $electionsEtabs
     */
    public function setElectionsEtabs($electionsEtabs) {
        $this->electionsEtabs = $electionsEtabs;
    }




    /**
     * Set typePrioritaire
     *
     * @param RefTypePrioritaire $typePrioritaire
     * @return RefEtablissement
     */
    public function setTypePrioritaire(RefTypePrioritaire $typePrioritaire = null)
    {
        $this->typePrioritaire = $typePrioritaire;

        return $this;
    }

    /**
     * Get typePrioritaire
     *
     */
    public function getTypePrioritaire()
    {
        return $this->typePrioritaire;
    }

    /**
     * Set uai_nature
     *
     * @param RefZoneNature $uaiNature
     * @return RefEtablissement
     */
    public function setUaiNature(RefZoneNature $uaiNature = null)
    {
        $this->uai_nature = $uaiNature;

        return $this;
    }

    /**
     * Get uai_nature
     *
     * @return RefZoneNature
     */
    public function getUaiNature()
    {
        return $this->uai_nature;
    }

    /**
     * Set date_fermeture
     *
     * @param \DateTime $dateFermeture
     * @return RefEtablissement
     */
    public function setDateFermeture($dateFermeture)
    {
        $this->date_fermeture = $dateFermeture;

        return $this;
    }

    /**
     * Get date_fermeture
     *
     * @return \DateTime
     */
    public function getDateFermeture()
    {
        //Lors de l'import RAMSESE, l'absence de date de fermeture est valorisée en base de données
        //par la valeur 0000-00-00 qui esst récupérée comme suit : -0001-11-30 00:00:00
        $valeurDateFermeture = $this->date_fermeture;
        if ($this->date_fermeture != null) {
            if ($this->date_fermeture->format('Y') == "-0001") {
                $valeurDateFermeture = null;
            }
        }
        return $valeurDateFermeture;
    }
}











