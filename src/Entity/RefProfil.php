<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * RefProfil
 *
 * @ORM\Table(name="ref_profil", options={"collate"="utf8_general_ci"})
 * @ORM\Entity
 */
class RefProfil
{
    const CODE_PROFIL_DE = 'ECOLE';
    const CODE_PROFIL_CE = 'ETAB';
    const CODE_PROFIL_IEN = 'IEN';
    const CODE_PROFIL_DSDEN = 'DSDEN';
    const CODE_PROFIL_RECT = 'RECT';
    const CODE_PROFIL_DGESCO = 'DGESCO';
    const CODE_PROFIL_PARENTS = 'FEDE';

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
     * @ORM\Column(name="libelle", type="string", length=64)
     */
    private $libelle;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=6)
     */
    private $code;

    /**
     * 		@var RefRole
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\RefRole", cascade={"persist"})
     * @ORM\JoinTable(name="ref_profil_role",joinColumns={@ORM\JoinColumn(name="id_profil", referencedColumnName="id")},inverseJoinColumns={@ORM\JoinColumn(name="id_role", referencedColumnName="id")})
     */
    private $roles;


    public function __construct() {
        $this->roles = new ArrayCollection();
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
     * @return RefProfil
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
     * Set code
     *
     * @param string $code
     * @return RefProfil
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
     * Add role
     *
     * @param RefRole $role
     */
    public function addRole(RefRole $role) {
        $this->roles[] = $role;
    }

    /**
     * Remove role
     *
     * @param RefRole $role
     */
    public function removeRole(RefRole $role) {
        $this->roles->removeElement($role);
    }

    /**
     * Get roles
     *
     * @return Collection
     */
    public function getRoles() {
        return $this->roles;
    }
}
