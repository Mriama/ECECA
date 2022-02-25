<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\RefAcademieFusion;

/**
 * RefAcademie
 * @ORM\Entity
 * @ORM\Entity
 * @ORM\Table(name="ref_academie", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\RefAcademieRepository", readOnly=true)
 */
class RefAcademie {
	
	const CODE_ACA_MAYOTTE = "MAO";
	const CODE_ACA_REUNION = "REU";
	
	/**
	 *
	 * @var integer 
	 * 		@ORM\Column(name="code", type="string", length=3)
	 *      @ORM\Id
	 */
	protected $code;
	
	/**
	 *
	 * @var string 
	 * 		@ORM\Column(name="libelle", type="string", length=255)
	 */
	protected $libelle;
	
	/**
	 *
	 * @var string
	 * 		@ORM\Column(name="code_email", type="string", length=50)
	 */
	protected $codeEmail;
	
	/**
	 *
	 * @var ArrayCollection $departements
	 * 
	 * 	@ORM\OneToMany(targetEntity="App\Entity\RefDepartement", mappedBy="academie", cascade={"persist"})    
	 */
	protected $departements;


    /**
     * @var \DateTime
     * @ORM\Column(name="date_activation", type="datetime")
     */
	protected $dateActivation;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_desactivation", type="datetime")
     */
	protected $dateDesactivation;

    /**
     * @return \DateTime
     */
    public function getDateActivation()
    {
        return $this->dateActivation;
    }

    /**
     * @return \DateTime
     */
    public function getDateDesactivation()
    {
        return $this->dateDesactivation;
    }

    /**
     * @param \DateTime $dateDesactivation
     */
    public function setDateDesactivation($dateDesactivation)
    {
        $this->dateDesactivation = $dateDesactivation;
    }

    /**
     * @param \DateTime $dateActivation
     */
    public function setDateActivation($dateActivation)
    {
        $this->dateActivation = $dateActivation;
    }


    /**
     * @ORM\ManyToOne(targetEntity="RefAcademieFusion", inversedBy="RefAcademies")
     * @ORM\JoinColumn(name="academiefusion_id", referencedColumnName="id")
     */

    protected  $AcademieFusion;

    /**
     * @return mixed
     */
    public function getAcademieFusion()
    {
        return $this->AcademieFusion;
    }

    /**
     * @param mixed $AcademieFusion
     */
    public function setAcademieFusion( RefAcademieFusion $AcademieFusion)
    {
        $this->AcademieFusion = $AcademieFusion;
    }

	
	/**
	 * Constructeur de base
	 */
	public function __construct(){
		$this->departements = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	/**
	 * Set code
	 *
	 * @param string $code        	
	 * @return RefAcademie
	 */
	public function setCode($code){
		$this->code = $code;
		
		return $this;
	}
	
	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getCode(){
		return $this->code;
	}
	
	/**
	 * Get idZone = code
	 *
	 * @return string
	 */
	public function getIdZone(){
		return $this->code;
	}
	
	/**
	 * Set libelle
	 *
	 * @param string $libelle        	
	 * @return RefAcademie
	 */
	public function setLibelle($libelle){
		$this->libelle = $libelle;
		
		return $this;
	}
	
	/**
	 * Get libelle
	 *
	 * @return string
	 */
	public function getLibelle(){
		return $this->libelle;
	}
	
	/**
	 * Set codeEmail
	 *
	 * @param string $codeEmail        	
	 * @return RefAcademie
	 */
	public function setCodeEmail($codeEmail){
		$this->codeEmail = $codeEmail;
		
		return $this;
	}
	
	/**
	 * Get codeEmail
	 *
	 * @return string
	 */
	public function getCodeEmail(){
		return $this->codeEmail;
	}
	
	/**
	 * Get departements
	 *
	 * @return Doctrine\Common\Collections\Collection
	 */
	public function getDepartements(){
		return $this->departements;
	}
	
	/**
	 * Set departements
	 *
	 * @param
	 *        	array of \App\Entity\RefDepartement $departements
	 */
	public function setDepartements($departements){
		$this->departements = $departements;
	}
	
	/**
	 * Get nom de l'entité
	 *
	 * @return "RefAcademie"
	 */
	public static function getNameEntity(){
		return 'RefAcademie';
	}

    /**
     * Add departements
     *
     * @param \App\Entity\RefDepartement $departements
     * @return RefAcademie
     */
    public function addDepartement(\App\Entity\RefDepartement $departements)
    {
        $this->departements[] = $departements;
    
        return $this;
    }

    /**
     * Remove departements
     *
     * @param \App\Entity\RefDepartement $departements
     */
    public function removeDepartement(\App\Entity\RefDepartement $departements)
    {
        $this->departements->removeElement($departements);
    }
}