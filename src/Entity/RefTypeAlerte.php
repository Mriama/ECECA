<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RefTypeAlerte
 *
 * @ORM\Table(name="ref_type_alerte", options={"collate"="utf8_general_ci"})
 * @ORM\Entity
 */
class RefTypeAlerte
{

    const CODE_CARENCE = 'CARENCE';
    const CODE_DEFICIT = 'DEFICIT';

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=10)
     * @ORM\Id
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255)
     */
    protected $libelle;


    /**
     * Set code
     *
     * @param string $code
     * @return RefTypeAlerte
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
     * @return RefTypeAlerte
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

}