<?php

namespace App\Form;

use App\Entity\EleCampagne;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampagneZoneEtabType extends ZoneEtabType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        parent::buildForm($builder, $options);

        $datas = $options;
        $refTypeElection = $datas['typeElect'];

        $params = array(
            'label' => '* Campagne',
            'multiple' => false,
            'class' => EleCampagne::class,
            'query_builder' => function(EntityRepository $er) use ($refTypeElection) {
                return $er->createQueryBuilder('c')
                    ->where('c.typeElection = :typeElection')
                    ->setParameter('typeElection', $refTypeElection)
                    ->groupBy('c.anneeDebut')
                    ->orderBy('c.anneeDebut', 'DESC');
            },
            'required' => true,
            'choice_label' => 'anneesDebFinCampagne'
        );

        $builder->add('campagne', EntityType::class, $params );
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => null,
            'typeElect' => null,
            'campagne' => null,
            'academie' => null,
            'departement' => null,
            'typeEtablissement' => null,
            'choix_etab' => null,
            'commune' => null,
            'etablissement' => null,
            'user' => null,
        ));
    }

    public function getBlockPrefix() { // Attention : Si changement ici ne pas oublier de modifier dans eple.js et eple.css
        return 'campagneZoneEtabType';
    }
}
