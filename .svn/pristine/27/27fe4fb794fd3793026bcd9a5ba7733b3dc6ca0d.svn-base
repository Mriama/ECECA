<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RefZoneNature
 *
 * @ORM\Table(name="ref_zone_nature", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\RefZoneNatureRepository")
 */
class RefZoneNature
{

    /**
     *
     * @var integer @ORM\Column(name="uai_nature", type="integer")
     *      @ORM\Id
     *     
     */
    protected $uai_nature;

    /**
     *
     * @var integer @ORM\ManyToOne(targetEntity="App\Entity\RefTypePrioritaire")
     *      @ORM\JoinColumn(name="id_type_prioritaire", referencedColumnName="id")
     */
    protected $typePrioritaire;

    /**
     *
     * @var string @ORM\Column(name="libelle_court", type="string", length=255)
     */
    protected $libelle_court;

    /**
     *
     * @var string @ORM\Column(name="libelle_long", type="string", length=255)
     */
    protected $libelle_long;

    /**
     *
     * @var string @ORM\Column(name="type_nature", type="string", length=255)
     */
    protected $type_nature;    
    
    
    
    /**
     * Constructeur de base
     */
    public function __construct()
    {
        $this->libelle_court = '';
        $this->libelle_long = '';
    }

    public function getUaiNature()
    {
        return $this->uai_nature;
    }

    public function setUaiNature($uai_nature)
    {
        $this->uai_nature = $uai_nature;
        return $this;
    }

    public function getTypePrioritaire()
    {
        return $this->typePrioritaire;
    }

    public function setTypePrioritaire($typePrioritaire)
    {
        $this->typePrioritaire = $typePrioritaire;
        return $this;
    }

    public function getLibelleCourt()
    {
        return $this->libelle_court;
    }

    public function setLibelleCourt($libelle_court)
    {
        $this->libelle_court = $libelle_court;
        return $this;
    }

    public function getLibelleLong()
    {
        return $this->libelle_long;
    }

    public function setLibelleLong($libelle_long)
    {
        $this->libelle_long = $libelle_long;
        return $this;
    }

    /**
     * Set type_nature
     *
     * @param string $typeNature
     * @return RefZoneNature
     */
    public function setTypeNature($typeNature)
    {
        $this->type_nature = $typeNature;
    
        return $this;
    }

    /**
     * Get type_nature
     *
     * @return string 
     */
    public function getTypeNature()
    {
        return $this->type_nature;
    }
}