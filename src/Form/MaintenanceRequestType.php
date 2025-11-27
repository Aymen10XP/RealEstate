<?php

namespace App\Form;

use App\Entity\MaintenanceRequest;
use App\Entity\Property;
use App\Entity\Manager;
use App\Repository\PropertyRepository;
use App\Repository\ManagerRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class MaintenanceRequestType extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();
        $isManager = $this->security->isGranted('ROLE_MANAGER');

        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Issue Title'
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 6],
                'label' => 'Description'
            ])
            ->add('priority', ChoiceType::class, [
                'choices' => [
                    'Low' => 'low',
                    'Medium' => 'medium',
                    'High' => 'high',
                    'Emergency' => 'emergency',
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'Priority Level'
            ])
        ;

        // Only show property field for tenants
        if (!$isManager && $user instanceof \App\Entity\Tenant) {
            $builder->add('property', EntityType::class, [
                'class' => Property::class,
                'choice_label' => 'address',
                'attr' => ['class' => 'form-control'],
                'label' => 'Select Property',
                'query_builder' => function (PropertyRepository $repository) use ($user) {
                    // Show only properties owned by this tenant's owner
                    return $repository->createQueryBuilder('p')
                        ->innerJoin('p.leases', 'l')
                        ->andWhere('l.tenant = :tenant')
                        ->andWhere('l.status = :status')
                        ->setParameter('tenant', $user)
                        ->setParameter('status', 'active')
                        ->orderBy('p.address', 'ASC');
                },
            ]);
        }

        // Additional fields for managers
        if ($isManager) {
            $builder
                ->add('status', ChoiceType::class, [
                    'choices' => [
                        'Submitted' => 'submitted',
                        'In Progress' => 'in_progress',
                        'Completed' => 'completed',
                        'Cancelled' => 'cancelled',
                    ],
                    'attr' => ['class' => 'form-control'],
                    'label' => 'Status'
                ])
                ->add('assignedManager', EntityType::class, [
                    'class' => Manager::class,
                    'choice_label' => function (Manager $manager) {
                        return $manager->getFullName() . ' (' . $manager->getEmail() . ')';
                    },
                    'attr' => ['class' => 'form-control'],
                    'label' => 'Assign Manager',
                    'required' => false,
                    'placeholder' => 'Not assigned',
                    'query_builder' => function (ManagerRepository $repository) {
                        return $repository->createQueryBuilder('m')
                            ->orderBy('m.firstName', 'ASC');
                    },
                ])
            ;

            // Show all properties for managers
            $builder->add('property', EntityType::class, [
                'class' => Property::class,
                'choice_label' => 'address',
                'attr' => ['class' => 'form-control'],
                'label' => 'Property',
                'query_builder' => function (PropertyRepository $repository) {
                    return $repository->createQueryBuilder('p')
                        ->orderBy('p.address', 'ASC');
                },
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MaintenanceRequest::class,
        ]);
    }
}