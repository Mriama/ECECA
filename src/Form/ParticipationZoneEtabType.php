<?php

namespace App\Form;

use App\Entity\EleCampagne;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class ParticipationZoneEtabType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $datas = $options['data'];
        $refTypeElection = $datas->getTypeElection();

        $builder->add('campagne', EntityType::class, array(
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
            'choice_label' => 'anneesDebFinCampagne'))

            ->add('niveau', ChoiceType::class,
                array(
                    'choices' => array(
                        'par département' => 'departement',
                        'par académie' => 'academie'
                    ),
                    'label' => '* Niveau de détail',
                    'multiple' => false,
                ));
    }


    public function getBlockPrefix() {
        return 'participationZoneEtabType';
    }
}