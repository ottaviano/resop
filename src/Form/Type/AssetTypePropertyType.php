<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\AssetType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetTypePropertyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('key', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'disable-on-edit'],
            ])
            ->add('type', ChoiceType::class, [
                'required' => true,
                'label' => 'Type',
                'choices' => array_combine(AssetType::TYPES, AssetType::TYPES),
                'placeholder' => '',
                'attr' => ['class' => 'disable-on-edit'],
            ])
            ->add('label', TextType::class, [
                'required' => true,
            ])
            ->add('help', TextType::class, [
                'required' => false,
            ])
            ->add('required', ChoiceType::class, [
                'required' => true,
                'multiple' => false,
                'expanded' => true,
                'choices' => ['oui' => true, 'non' => false],
            ])
            ->add('hidden', ChoiceType::class, [
                'required' => true,
                'multiple' => false,
                'expanded' => true,
                'choices' => ['oui' => true, 'non' => false],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'row_attr' => [
                'class' => 'shadow p-3 mt-3 rounded col-sm-10 mx-auto',
            ],
        ]);
    }
}
