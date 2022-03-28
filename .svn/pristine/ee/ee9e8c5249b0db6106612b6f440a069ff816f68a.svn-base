<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\RefAcademie;
use App\Entity\RefDepartement;

class RefCommuneRepository extends EntityRepository
{

    public function queryBuilderFindCommuneParZone($zone = null)
    {

        $query = $this->createQueryBuilder('c')->leftjoin('c.departement', 'd')->addSelect('d');

        if ($zone instanceof RefDepartement) {
            $query = $query->where('d.numero = :dep');
            $query = $query->setParameter('dep', $zone);
        }
        if ($zone instanceof RefAcademie) {
            $query = $query->where('d.academie = :aca');
            $query = $query->setParameter('aca', $zone);
        }

        $query = $query->orderBy('c.libelle', 'ASC')->addOrderBy('c.codePostal', 'ASC');

        return $query;
    }

    /**
     * @param : $zone : Entity RefAcademie ou RefDepartement
     * @return ArrayCollection of RefCommune composé de l'ensemble des communes de l'académie ou du département
     * "findCommuneParZone" permet de récupérer l'ensemble des communes associés à une académie ou un département
     */
    public function findCommuneParZone($zone = null)
    {
        return $this->queryBuilderFindCommuneParZone($zone)->getQuery()->getResult();
    }

    /**
     * @return nb of RefCommune supprimées
     * "deleteCommuneSansEtab" permet de supprimer l'ensemble des communes associés à aucun etablissement
     */
    public function deleteCommuneSansEtab()
    {

        $query_etab = $this->_em->createQueryBuilder();
        $query_etab->add('select', 'com.id')
            ->add('from', 'App\Entity\RefEtablissement e')
            ->join('e.commune', 'com')
            ->add('where', 'e.commune is not null')
            ->distinct('e.commune');

        $query_com = $this->_em->createQueryBuilder();
        $query_com->add('select', 'c')
            ->add('from', 'App\Entity\RefCommune c')
            ->where($query_com->expr()->notIn('c.id', $query_etab->getDQL()));
        $liste_commune = $query_com->getQuery()->getResult();

        $array = array();
        foreach ($liste_commune as $key => $commune) {
            $array[$key] = $commune->getId();
        }

        if (count($array) != 0) {
            $str_communeIds = implode(" ', '", $array);

            $stringQuery = "	DELETE FROM App\Entity\RefCommune c WHERE (c.id in ( '" . $str_communeIds . "' ))";
            $query = $this->_em->createQuery($stringQuery);

            return $query->getResult();
        }
        return 0;
    }

    /**
     * retourne un tableau associatif code_postal => id de ref_commune, utilisé dans l'import ramsese
     *
     */
    public function getArrayRefCommune()
    {
        $sql = "SELECT id, code_postal FROM ref_commune";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();
        $array = array();
        while ($row = $stmt->fetch()) {
            $array[$row['code_postal']] = $row['id'];
        }

        return $array;
    }
    /**
     * retourne un tableau associatif code_postal => id de ref_commune, utilisé dans l'import ramsese
     *
     */
    public function getArrayRefCommuneByCodeInsee()
    {
        $sql = "SELECT id, departement, libelle, code_postal, code_insee FROM ref_commune";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();
        $array = array();
        while ($row = $stmt->fetch()) {
            $array[$row['code_insee']] = array(
                'id' => $row['id'],
                'departement' => $row['departement'],
                'libelle' => $row['libelle'],
                'code_postal' => $row['code_postal'],
                'code_insee' => $row['code_insee']
            );
        }

        return $array;
    }

    /**
     *
     * @param unknown $codeInsee
     */
    public function findIdParCodeInsee($listeDonneesAMettreAJour)
    {
        $sql = "SELECT MIN(id) as id, code_insee FROM ref_commune ";
        $sql .= "WHERE code_insee IN (";

// 	    echo '<pre>';
// 	    var_dump($listeDonneesAMettreAJour);
// 	    echo '</pre>';
// 	    die();

        foreach ($listeDonneesAMettreAJour as $key => $array) {
            $sql .= '"' . $array['insee'] . '",';
        }
        $sql = substr($sql, 0, (strlen($sql) - 1)) . ") GROUP BY code_insee";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();
        $result = array();
        while ($row = $stmt->fetch()) {
            $result[$row['code_insee']] = $row['id'];
        }
        return $result;
    }


    /**
     * Insertion / Mise à jour des communes via Import avec le fichier uairefco
     */
	public function insertListeRefCommunesByImport($listeRefCommunesImport)
    {
        $db = $this->_em->getConnection();
        $j = 0;
        $sizeArray = sizeof($listeRefCommunesImport);
        $tmpArray = array_chunk($listeRefCommunesImport, 500);
        foreach($tmpArray as $tmp){
        	foreach ($tmp as $refCommuneImport) {
        		if ($j == 0) {
        			$query = "INSERT INTO ref_commune (	id,
                                                    departement,
                                                    libelle,
                                                    code_postal,
                                                    code_insee)
                                                    VALUES ";
        		}
        	
        		$query .= "(" . $db->quote($refCommuneImport->getId()) . ","
        				. $db->quote($refCommuneImport->getDepartement()->getNumero()) . ","
        						. $db->quote($refCommuneImport->getLibelle()) . ","
        								. $db->quote($refCommuneImport->getCodePostal()) . ","
        										. $db->quote($refCommuneImport->getCodeInsee())
        										. "),";
        	
        		$j++;
        		if ($j == 1000) {
        			$query = substr($query, 0, strlen($query) - 1);
        			$query .= " ON DUPLICATE KEY UPDATE libelle = VALUES(libelle),
                                                    departement = VALUES(departement),
                                                    code_postal = VALUES(code_postal),
                                                    code_insee = VALUES(code_insee)";
        	
        			$stmt = $db->prepare($query);
        			$params = array();
        			$stmt->execute($params);
        	
        			$j = 0;
        		}
        	}
        	
        	if ($j > 0) {
        		$query = substr($query, 0, strlen($query) - 1);
        		$query .= " ON DUPLICATE KEY UPDATE libelle = VALUES(libelle),
                                                departement = VALUES(departement),
                                                code_postal = VALUES(code_postal),
                                                code_insee = VALUES(code_insee)";
        	
        		$stmt = $db->prepare($query);
        		$params = array();
        		$stmt->execute($params);
        	
        		$j = 0;
        	}
        }
        
    }

    //return integer
    //retourne le nombre total des enregistrement de la table ref_commune
    public function countAllCommunes()
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->getQuery()
            ->getSingleScalarResult();
    }

}