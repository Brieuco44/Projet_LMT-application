<?php

namespace App\Form;

use App\Entity\Zone;
use App\Entity\Champs;
use App\Entity\TypeChamps;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ChampsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $headers = array_filter($options['headers'], fn($v) => !empty($v));

        $builder
            ->add('nom')
            ->add('question',TextType::class, [
                'required' => false,
            ])
            ->add('donneeERP', ChoiceType::class, [
                'choices' => array_combine($headers, $headers),
                'label' => 'Donnée ERP',
                'placeholder' => 'Sélectionner une donnée ERP',
                'required' => false,
            ])
            ->add('typeChamps', EntityType::class, [
                'class'        => TypeChamps::class,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $return = $er->createQueryBuilder('tc');
                        // ->orderBy('tc.nom', 'DESC');
                    if ($options['hasIdentifiant']) {
                        $return->andWhere('tc.nom!= :nomTC')
                            ->setParameter('nomTC', 'Identifiant');
                    }
                    return $return;
                },
                'choice_label' => 'nom',
                'label' => 'Type de champ',
                'placeholder' => 'Sélectionner un type de champ',
                'choice_attr'  => fn(TypeChamps $tc) => ['data-nom' => $tc->getNom()],
            ])
            ->add('zone', EntityType::class, [
                'class' => Zone::class,
                'choice_label' => 'id',
                'data' => $options['zone'] ?? null,

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Champs::class,
            'headers' => [],
            'hasIdentifiant' => false,
            'zone' => null,
        ]);
    }
}
