<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * RefAcademieFusion
 *
 * @ORM\Table(name="ref_academie_fusion")
 * @ORM\Entity(repositoryClass="App\Repository\RefAcademieFusionRepository")
 */
class RefAcademieFusion
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
     * @ORM\Column(name="code", type="string", length=3)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255)
     */
    private $libelle;


    /**
     * @ORM\OneToMany(targetEntity="RefAcademie", mappedBy="AcademieFusion")
     */
    protected $RefAcademies;

    public function __construct()
    {
        $this->RefAcademies = new ArrayCollection();
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
     * Set code
     *
     * @param string $code
     * @return RefAcademieFusion
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set libelle
     *
     * @param string $libelle
     * @return RefAcademieFusion
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
     * @return ArrayCollection
     */
    public function getRefAcademies()
    {
        return $this->RefAcademies;
    }

    /**
     * @param ArrayCollection $RefAcademies
     */
    public function setRefAcademies($RefAcademies)
    {
        $this->RefAcademies = $RefAcademies;
    }

}
