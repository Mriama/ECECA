<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class ParticipationZoneEtabType extends AbstractType {
 	
	
	protected $degresUser; // N'afficher que les types d'établissement correspondant au degrés de l'utilisateur
	protected $user;

	public function __construct(TokenStorageInterface $tokenStorage) {
		$this->user = $tokenStorage->getToken()->getUser();
		$this->degresUser = $this->user->getPerimetre()->getDegres();
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
	
	$datas = $options['data'];
    $refTypeElection = $datas->getTypeElection();
    	
	$builder->add('campagne', 'entity', array(
			'label' => '* Campagne',
			'multiple' => false,
			'class' => 'EPLEElectionBundle:EleCampagne',
			'query_builder' => function(\Doctrine\ORM\EntityRepository $er) use ($refTypeElection) {
				return $er->createQueryBuilder('c')
				->where('c.typeElection = :typeElection')
				->setParameter('typeElection', $refTypeElection)
				->groupBy('c.anneeDebut')
				->orderBy('c.anneeDebut', 'DESC');
			},
			'required' => true,
			'property' => 'anneesDebFinCampagne'))
			
	->add('niveau', 'choice',
					array('choices' => array('departement' => 'par département', 'academie' => 'par académie'),
							'label' => '* Niveau de détail',
							'multiple' => false,
					));
    }
	
	
    public function getName() {
        return 'participationZoneEtabType';
    }
}