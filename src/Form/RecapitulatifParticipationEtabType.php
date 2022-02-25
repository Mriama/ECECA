<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Form\ParticipationZoneEtabType;

class RecapitulatifParticipationEtabType extends ParticipationZoneEtabType{
	
	protected $user;
	public function __construct(TokenStorageInterface $tokenStorage) {
		$this->user = $tokenStorage->getToken()->getUser();
		parent::__construct($user);
		$this->user = $user;

	}
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
			
		parent::buildForm($builder, $options);
		
		$degres = ($this->degresUser != null) ? $this->degresUser : null;
		//$datas = $options['data'];
		//$refTypeElection = $datas->getTypeElection();
		
		$builder->add('typeEtablissement', 'entity', array(
			'label' => 'Type d\'établissement',
			'multiple' => false,
			'class' => 'EPLEElectionBundle:RefTypeEtablissement',
			'query_builder' => function(\Doctrine\ORM\EntityRepository $er) use ($degres) {
				$qb = $er->createQueryBuilder('t');
				if (!empty($degres)) {
					$qb->where('t.degre in (:degres)')
					->setParameter('degres', $degres);
				}
				$qb->orderBy('t.ordre', 'ASC');
				return $qb;
			},
			'required' => false,
			'property' => 'code',
			'empty_value' => 'Tous'));
		
	}
	
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
				'data_class' => 'App\Model\RecapitulatifParticipationEtabTypeModel'
		));
	}
	
	public function getName() {
		return 'recapitulatifParticipationEtabType';
	}
}