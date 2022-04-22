<?php

namespace App\Form;

use App\Entity\RefProfil;
use App\Entity\EleEtablissement;
use App\Entity\RefSousTypeElection;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ResultatZoneEtabType extends ZoneEtabType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);

        $hasSousTypeElect = $options["hasSousTypeElect"];

// 		if (!$this->campagne->isFinished()) { // mantis 122046 le filtre avancement des saisies apparait tout le temps
        $builder->add('etatSaisie', ChoiceType::class, array(
                'label' => 'Avancement des saisies',
                'multiple' => true,
                'expanded' => true,
                'choices' => array(
                    'Enregistrées' => EleEtablissement::ETAT_SAISIE,
                    'Transmises' => EleEtablissement::ETAT_TRANSMISSION,
                    'Validées' => EleEtablissement::ETAT_VALIDATION
                ),
                'data' => array(EleEtablissement::ETAT_VALIDATION),
                'required' => true)
        );

        if( $hasSousTypeElect ){
            $builder ->add('sousTypeElection', EntityType::class, array(
                'label' => 'Sous-type d’élection',
                'multiple' => false,
                'class' => RefSousTypeElection::class,
                'query_builder' => function(EntityRepository $er){
                    $qb = $er->createQueryBuilder('s');
                    $qb->orderBy('s.id', 'ASC');
                    return $qb;
                },
                'required' => false,
                'choice_label' => 'code',
                'placeholder' => false));
        }
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => null,
            'hasSousTypeElect' => null,
            'user' => null
        ));
    }

    public function getBlockPrefix() {
        return 'resultatZoneEtabType';
    }

}
