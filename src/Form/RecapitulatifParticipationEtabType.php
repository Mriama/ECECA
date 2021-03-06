<?php
namespace App\Form;

use App\Entity\RefTypeEtablissement;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;


class RecapitulatifParticipationEtabType extends ParticipationZoneEtabType{

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);

        $user = $options["user"] ?? null;
        $degres = ($user != null && $user->getPerimetre()->getDegres() != null) ? $user->getPerimetre()->getDegres() : null;
        //$datas = $options['data'];
        //$refTypeElection = $datas->getTypeElection();

        $builder->add('typeEtablissement', EntityType::class, array(
            'label' => 'Type d\'√©tablissement',
            'multiple' => false,
            'class' => RefTypeEtablissement::class,
            'query_builder' => function(EntityRepository $er) use ($degres) {
                $qb = $er->createQueryBuilder('t');
                if (!empty($degres)) {
                    $qb->where('t.degre in (:degres)')
                        ->setParameter('degres', $degres);
                }
                $qb->orderBy('t.ordre', 'ASC');
                return $qb;
            },
            'required' => false,
            'choice_label' => 'code',
            'placeholder' => 'Tous'));

    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'App\Model\RecapitulatifParticipationEtabTypeModel',
            'user' => null
        ));
    }

    public function getBlockPrefix() {
        return 'recapitulatifParticipationEtabType';
    }
}