<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RefRole
 *
 * @ORM\Table(name="ref_role", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\RefRoleRepository")
 */
class RefRole
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
     * @ORM\Column(name="nom", type="string", length=128)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=20)
     */
    private $role;
    
    
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
     * Set nom
     *
     * @param string $nom
     * @return RefRole
     */
    public function setNom($nom)
    {
        $this->nom = $nom;
    
        return $this;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set role
     *
     * @param string $role
     * @return RefRole
     */
    public function setRole($role)
    {
        $this->role = $role;
    
        return $this;
    }

    /**
     * Get role
     *
     * @return string 
     */
    public function getRole()
    {
        return $this->role;
    }
    
    
    public function serialize() {
    	return \json_encode(array(
    			$this->id,
    			$this->nom,
    			$this->role,
    	));
    }
    
    public function unserialize($serialized) {
    	list(
    			$this->id,
    			$this->nom,
    			$this->role,
    	) = \json_decode($serialized);
    }
}
