<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\AssetType;
use App\Entity\CommissionableAsset;
use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CommissionableAssetType extends AbstractType
{
    public const TYPES = [
        'VL - Véhicule léger' => 'VL',
        'VPSP - Véhicule de premiers secours' => 'VPSP',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Organization $organization */
        $organization = $builder->getForm()->getData()->organization;
        /** @var AssetType $assetType */
        $assetType = $builder->getForm()->getData()->assetType;

        $builder
            ->add('type', ChoiceType::class, [
                'choices' => self::TYPES,
                'label' => 'asset.type',
            ])
            ->add('name', TextType::class, [
                'label' => 'asset.name',
            ])
            ->add('hasMobileRadio', ChoiceType::class, [
                'choices' => [
                    'common.yes' => true,
                    'common.no' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'asset.hasMobileRadio',
            ])
            ->add('hasFirstAidKit', ChoiceType::class, [
                'choices' => [
                    'common.yes' => true,
                    'common.no' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'asset.hasFirstAidKit',
            ])
            ->add('parkingLocation', TextType::class, [
                'required' => false,
                'label' => 'asset.parkingLocation',
            ])
            ->add('contact', TextType::class, [
                'required' => false,
                'label' => 'asset.contact',
            ])
            ->add('seatingCapacity', IntegerType::class, [
                'label' => 'asset.seatingCapacity',
            ])
            ->add('licensePlate', TextType::class, [
                'label' => 'asset.licensePlate',
            ])
            ->add('comments', TextareaType::class, [
                'label' => 'asset.comments',
            ])
            ->add('properties', AssetPropertiesType::class, ['inherit_data' => true,'config' => $assetType->properties])
            ->add('submit', SubmitType::class)
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($organization): void {
            $form = $event->getForm();
            if ($organization->isParent()) {
                $form->add('organization', OrganizationEntityType::class, [
                    'placeholder' => '',
                    'query_builder' => function (OrganizationRepository $repository) use ($organization) {
                        return $repository->findByIdOrParentIdQueryBuilder($organization->getId());
                    },
                ]);
            }
        });


        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            dump($event->getData()); // properties is ok here but then its not persisted 
//
            dump($event->getForm()->getData());
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommissionableAsset::class,
        ]);
    }
}
