<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\RefZoneNature;


class RefZoneNatureRepository extends EntityRepository{

    public function findOneByTypeNature($typeNature){
        $query = $this->createQueryBuilder('nature')
            ->where("nature.type_nature =:typeNature")
            ->setParameter('typeNature', $typeNature);
        return $query->getQuery()->getResult();
    }
}
?>