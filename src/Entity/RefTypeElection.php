<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * RefTypeElection
 *
 * @ORM\Table(name="ref_type_election", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\RefTypeElectionRepository")
 */
class RefTypeElection
{

    const ID_TYP_ELECT_PARENT = 3;
    const ID_TYP_ELECT_ASS_ATE = 1;
    const ID_TYP_ELECT_PEE = 2;
    const CODE_URL_ASS_ATE = 'ass_ate';
    const CODE_URL_PEE = 'pee';
    const CODE_URL_PARENT = 'parent';
    const CODE_URL_A_ATTE = 'a_atte';
    const CODE_URL_SS = 'ss';

    const CODE_PE = 'pe';
    const CODE_RP = 'rp';

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
     * @ORM\Column(name="code", type="string", length=50)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255)
     */
    private $libelle;

    /**
     * @var ArrayCollection $organisations
     */
    private $organisations;

    public function __construct() {
        $this->organisations = new ArrayCollection();
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
     * @return RefTypeElection
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
     * @return RefTypeElection
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
     * Get organisations
     *
     * @return Collection
     */
    public function getOrganisations() {
        return $this->organisations;
    }

    /**
     * Set organisations
     *
     * @param array of \App\Entity\RefOrganisation $organisations
     */
    public function setOrganisations($organisations) {
        $this->organisations = $organisations;
    }

    /********************************************* LOGIQUE METIER ***************************************/

    public static function getIdRefTypeElectionByCodeUrl($codeUrl='') {
        $id = null;
        switch($codeUrl) {
            case self::CODE_URL_ASS_ATE:
                $id = 1;
                break;
            case self::CODE_URL_PEE:
                $id = 2;
                break;
            case self::CODE_URL_PARENT:
                $id = 3;
                break;
            case self::CODE_URL_A_ATTE:
                $id = 1;
                break;
            case self::CODE_URL_SS:
                $id = 1;
                break;
        }
        return $id;
    }

    public static function getCodesUrls() {
        return array(
            1=>self::CODE_URL_ASS_ATE,
            2=>self::CODE_URL_PEE,
            3=>self::CODE_URL_PARENT);
    }

    public function getCodeUrlById() {
        $urls = $this->getCodesUrls();
        return $urls[$this->id];
    }


    /**
     * Set id
     *
     * @param integer $id
     * @return RefTypeElection
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}