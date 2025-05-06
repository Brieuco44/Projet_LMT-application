<?php

namespace App\Form;

use App\Entity\Zone;
use App\Entity\Champs;
use App\Entity\TypeChamps;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChampsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $headers = array_filter($options['headers'], fn($v) => !empty($v));

        $builder
            ->add('nom')
            ->add('question')
            ->add('donneeERP', ChoiceType::class, [
                'choices' => array_combine($headers, $headers),
                'placeholder' => 'Sélectionner une donnée ERP',
                'required' => false,
            ])
            ->add('typeChamps', EntityType::class, [
                'class' => TypeChamps::class,
                'choice_label' => 'nom',
            ])
            ->add('zone', EntityType::class, [
                'class' => Zone::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Champs::class,
            'headers' => [], // <- option personnalisée
        ]);
    }
}
