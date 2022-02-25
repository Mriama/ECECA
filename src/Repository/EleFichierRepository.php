<?php
namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * EleFichierRepository
 */
class EleFichierRepository extends EntityRepository
{    
    /**
     * 
     * @param unknown $yearOld
     */
    public function findObsolete($yearOld){
    	$qb = $this->createQueryBuilder('eleFichier');
    	$qb->where('eleFichier.date < :date');
    	$qb->setParameter('date', new \DateTime('-'.$yearOld.' year'));
    	return $qb->getQuery()->getResult();
    }
}
