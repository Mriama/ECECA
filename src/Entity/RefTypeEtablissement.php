<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * RefTypeEtablissement
 *
 * @ORM\Table(name="ref_type_etablissement", options={"collate"="utf8_general_ci"})
 * @ORM\Entity
 */
class RefTypeEtablissement
{

    const CODE_URL_ECOLE = 'ecole';
    const CODE_URL_2ND_DEGRE = 'second_degre';
    const CODE_URL_COLL= 'college';
    const CODE_URL_LYC_GEN = 'lycee_general';
    const CODE_URL_LYC_PRO = 'lycee_pro';
    const CODE_URL_EREA_ERPD = 'erea_erpd';
    const CODE_URL_2ND_DGRE = '2nd_degre';

    const ID_TYP_1ER_DEGRE = 1;
    const ID_TYP_COLLEGE = 2;
    const ID_TYP_LYCEE = 3;
    const ID_TYP_LYC_PRO = 4;
    const ID_TYP_EREA_ERPD = 5;
    const ID_TYP_2ND_DEGRE = 6;

    const CODE_EREA_ERPD = 'EREA-ERPD';

    const SECOND_DEGRE = 2;

    /**
     * @var integer
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=50)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255)
     */
    protected $libelle;

    /**
     * @var string
     *
     * @ORM\Column(name="degre", type="string", length=1)
     */
    protected $degre;

    /**
     * @var integer
     *
     * @ORM\Column(name="has_eclair", type="boolean")
     */
    protected $hasEclair;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordre", type="integer")
     */
    protected $ordre;

    /**
     * @var ArrayCollection $etablissements
     */
    protected $etablissements;

    public function __construct() {
        $this->hasEclair = false;
        $this->etablissements = new ArrayCollection();
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
     * @return RefTypeEtablissement
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
     * @return RefTypeEtablissement
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
     * Set degre
     *
     * @param string $degre
     * @return RefTypeEtablissement
     */
    public function setDegre($degre)
    {
        $this->degre = $degre;

        return $this;
    }

    /**
     * Get degre
     *
     * @return string
     */
    public function getDegre()
    {
        return $this->degre;
    }

    /**
     * Set hasEclair
     *
     * @param integer $hasEclair
     * @return RefTypeEtablissement
     */
    public function setHasEclair($hasEclair)
    {
        $this->hasEclair = $hasEclair;

        return $this;
    }

    /**
     * Get hasEclair
     *
     * @return integer
     */
    public function getHasEclair()
    {
        return $this->hasEclair;
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

    /********************************** LOGIQUE METIER ****************************************/

    public static function getIdRefTypeEtabByCodeUrl($codeUrl='') {
        $id = null;
        switch($codeUrl) {
            case self::CODE_URL_2ND_DEGRE:
                $id = '2,3,4,5';
                break;
            case self::CODE_URL_ECOLE:
                $id = 1;
                break;
            case self::CODE_URL_COLL:
                $id = 2;
                break;
            case self::CODE_URL_LYC_GEN:
                $id = 3;
                break;
            case self::CODE_URL_LYC_PRO:
                $id = 4;
                break;
            case self::CODE_URL_EREA_ERPD:
                $id = 5;
                break;
            case self::CODE_URL_2ND_DGRE:
                $id = '6';
                break;
        }
        return $id;
    }

    public static function getCodesUrls() {
        return array(
            1=>self::CODE_URL_ECOLE,
            2=>self::CODE_URL_COLL,
            3=>self::CODE_URL_LYC_GEN,
            4=>self::CODE_URL_LYC_PRO,
            5=>self::CODE_URL_EREA_ERPD,
            6=>self::CODE_URL_2ND_DGRE);
    }

    public function getCodeUrlById() {
        $urls = $this->getCodesUrls();
        return $urls[$this->id];
    }


    /**
     * Set id
     *
     * @param integer $id
     * @return RefTypeEtablissement
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getOrdre()
    {
        return $this->ordre;
    }

    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;
        return $this;
    }

}