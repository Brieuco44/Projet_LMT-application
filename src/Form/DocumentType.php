<?php

namespace App\Form;

use App\Entity\Document;
use App\Entity\TypeLivrable;
use App\Repository\TypeLivrableRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('TypeLivrable', EntityType::class, [
                'class' => TypeLivrable::class,
                'choice_label' => 'nom',
                'query_builder' => function (TypeLivrableRepository $tr) {
                    return $tr->createQueryBuilder('t')
                        ->orderBy('t.nom', 'ASC');
                },
                'label' => 'Type de livrable',
                'attr' => [
                    'class' => 'select select-bordered',
                ],
                'placeholder' => '-- Type de document --',
            ])
            ->add('files', FileType::class, [
                'label' => 'Fichiers',
                'attr' => [
                    'class' => 'file-input',
                    'multiple' => true,
                    'accept' => '.pdf',
                ],
                'mapped' => false, // This field is not mapped to the entity
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class, // Specify the data class for the form
            // 'allow_extra_fields' => true,
        ]);
    }
}
