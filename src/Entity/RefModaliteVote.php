<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RefModaliteVote
 *
 * @ORM\Table(name="ref_modalite_vote", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\RefModaliteVoteRepository")
 */
class RefModaliteVote {

    const ID_MODALITE_VOTE_URNE_CORRESPONDANCE = 1;
    const LIBELLE_MODALITE_VOTE_URNE_CORRESPONDANCE = "à l'urne et par correspondance";
    const ID_MODALITE_VOTE_CORRESPONDANCE = 2;
    const LIBELLE_MODALITE_VOTE_CORRESPONDANCE = "exclusivement par correspondance";

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    private $id;

       /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255)
     */
    private $libelle;

    public function __construct() {}

    /**
     * Set id
     *
     * @param integer $id
     * @return RefModaliteVote
     */
    public function setId($id)
    {
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
     * @return RefModaliteVote
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

    public function __toString() {
        return $this->libelle;
    }
}