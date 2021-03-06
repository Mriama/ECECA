<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * RefCommune
 *
 * @ORM\Table(name="ref_commune", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\RefCommuneRepository", readOnly=true)
 */
class RefCommune
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
     * @var string
     *
     * @ORM\Column(name="code_postal", type="string", length=255)
     */
    protected $codePostal;

    /**
     * @ORM\Column(name="code_insee", type="string", length=5, nullable=true)
     * @var int
     */
    protected $codeInsee;

    /**
     * 		@var RefDepartement
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\RefDepartement")
     * @ORM\JoinColumn(name="departement", referencedColumnName="numero")
     */
    protected $departement;

    /**
     * @var ArrayCollection $etablissements
     */
    protected $etablissements;

    /**
     * Constructeur de base
     */
    public function __construct() {
        $this->id = 0;
        $this->etablissements = new ArrayCollection();
    }

    public function setId($id){
        $this->id = $id;
        return $this;
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
     * @return RefCommune
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
     * Set codePostal
     *
     * @param string $codePostal
     * @return RefCommune
     */
    public function setCodePostal($codePostal)
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    /**
     * Get codePostal
     *
     * @return string
     */
    public function getCodePostal()
    {
        return $this->codePostal;
    }

    /**
     *
     * @return number
     */
    public function getCodeInsee()
    {
        return $this->codeInsee;
    }

    /**
     *
     * @param $codeInsee
     * @return RefCommune
     */
    public function setCodeInsee($codeInsee)
    {
        $this->codeInsee = $codeInsee;
        return $this;
    }

    /**
     * Set departement
     *
     * @param RefDepartement $departement
     */
    public function setDepartement(
        RefDepartement $departement) {
        $this->departement = $departement;
    }

    /**
     * Get departement
     *
     * @return RefDepartement
     */
    public function getDepartement() {
        return $this->departement;
    }

    /**
     * Get etablissements
     *
     * @return Collection
     */
    public function getEtablissements() {
        return $this->etablissements;
    }

    /**
     * Set etablissements
     *
     * @param array of \App\Entity\RefEtablissement $etablissements
     */
    public function setEtablissements($etablissements) {
        $this->etablissements = $etablissements;
    }

    /********************************************* LOGIQUE METIER **********************************************/

    public function isCodePostalValid(ExecutionContextInterface $context) {

        if ( !is_numeric($this->codePostal) ) {
            $context->addViolation('Le code postal doit ??tre uniquement compos?? de chiffres.', array($this->codePostal));
        } else{
            $cp = (int) $this->codePostal;
            if( $cp <= 1000){
                $context->addViolation('Le code postal doit ??tre superieur ou ??gal ?? 0', array('01000'));
            }
        }
    }




}
