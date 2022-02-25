<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RefPerimetre
 *
 * @ORM\Table(name="ref_perimetre", options={"collate"="utf8_general_ci"})
 * @ORM\Entity
 */
class RefPerimetre
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
     * @ORM\Column(name="type_elections", type="string", length=255, nullable=true)
     */
    private $typeElections;

    /**
     * @var string
     *
     * @ORM\Column(name="etablissements", type="text", nullable=true)
     */
    private $etablissements;

    /**
     * @var string
     *
     * @ORM\Column(name="degres", type="string", length=255, nullable=true)
     */
    private $degres;

    /**
     * @var string
     *
     * @ORM\Column(name="academie", type="string", length=255, nullable=true)
     */
    private $academie;

    /**
     * @var string
     *
     * @ORM\Column(name="departement", type="string", length=255, nullable=true)
     */
    private $departement;

    /**
     * @var string
     *
     * @ORM\Column(name="commune", type="string", length=255, nullable=true)
     */
    private $commune;
        
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
     * Set typeElections
     *
     * @param string $typeElections
     * @return RefPerimetre
     */
    public function setTypeElections($typeElections)
    {
        $this->typeElections = $typeElections;
    
        return $this;
    }

    /**
     * Get typeElections
     *
     * @return string 
     */
    public function getTypeElections()
    {
        return $this->typeElections;
    }

    /**
     * Set etablissements
     *
     * @param string $etablissements
     * @return RefPerimetre
     */
    public function setEtablissements($etablissements)
    {
        $this->etablissements = $etablissements;
    
        return $this;
    }

    /**
     * Get etablissements
     *
     * @return string 
     */
    public function getEtablissements()
    {
        return $this->etablissements;
    }

    /**
     * Set degres
     *
     * @param string $degres
     * @return RefPerimetre
     */
    public function setDegres($degres)
    {
        $this->degres = $degres;
    
        return $this;
    }

    /**
     * Get degres
     *
     * @return string 
     */
    public function getDegres()
    {
        return $this->degres;
    }

    /**
     * Set academie
     *
     * @param string $academie
     * @return RefPerimetre
     */
    public function setAcademie($academie)
    {
        $this->academie = $academie;
    
        return $this;
    }

    /**
     * Get academie
     *
     * @return string 
     */
    public function getAcademie()
    {
        return $this->academie;
    }

    /**
     * Set departement
     *
     * @param string $departement
     * @return RefPerimetre
     */
    public function setDepartement($departement)
    {
        $this->departement = $departement;
    
        return $this;
    }

    /**
     * Get departement
     *
     * @return string 
     */
    public function getDepartement()
    {
        return $this->departement;
    }

    /**
     * Set commune
     *
     * @param string $commune
     * @return RefPerimetre
     */
    public function setCommune($commune)
    {
        $this->commune = $commune;
    
        return $this;
    }
    
    /**
     * Get commune
     *
     * @return string 
     */
    public function getCommune()
    {
        return $this->commune;
    }

    /**
     * 
     * @param \App\Utils\RefUserPerimetre $refUserPerimetre
     * @param unknown $token
     */
    public function __construct(\App\Utils\RefUserPerimetre $refUserPerimetre){
    	    	
    	$this->typeElections = null;
    	$tmp = array();
    	    	
    	foreach($refUserPerimetre->getTypeElections() as $typeElec){
    		array_push($tmp, $typeElec->getId());
    	}
    	$this->typeElections = implode(',',$tmp);
    	
    	// TODO autres données du périmètre;
    	
    }

	
}