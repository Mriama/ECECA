<?php

namespace App\Form;

use App\Form\EleParticipationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class EleEtablissementType extends AbstractType {

    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

		$builder->add('participation', EleParticipationType::class,array('label' => '* Participation'))
				->add('resultats', CollectionType::class, array(
					    'entry_type'   => new EleResultatType(),
					    'entry_options'  => array('required'  => false)))
				->add('resultatsDetailles', CollectionType::class, array(
					    'entry_type'   => new EleResultatDetailType($this->em),
					    'entry_options'  => array('required'  => false),
				        'allow_add'    => true,
				        'allow_delete' => true,
				        ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\EleEtablissement'
        ));
    }

    public function getBlockPrefix() {
        return 'EleEtablissementType';
    }
}
