<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RefTypePrioritaire
 *
 * @ORM\Table(name="ref_type_prioritaire", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\RefTypePrioritaireRepository")
 */
class RefTypePrioritaire
{

    const REF_TYPE_PRIORITAIRE_AUTRE = 'SANS OBJET';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=10)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255, nullable=true)
     */
    protected $libelle;


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
     * @return RefTypePrioritaire
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
     * @return RefTypePrioritaire
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
     * Set id
     *
     * @param integer $id
     * @return RefTypePrioritaire
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}