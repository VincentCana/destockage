<?php

namespace DiscountBundle\Form;

use DiscountBundle\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use DiscountBundle\Form\DataTransformer\PictureToStringTransformer;
use Doctrine\Common\Persistence\ObjectManager;

class ProductUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class)
            ->add('discountType', ChoiceType::class, [
                'choices'=> ['€'=>'0','%'=>'1'],
                'required' =>true,
                'expanded' =>true,
                'multiple' => false
                ])
            ->add('discountValue', NumberType::class, [
                    'grouping' => false,
                    'attr' => [
                        'min' => 0,
                        'step' => 0.01,
                    ],
                ])
            ->add('picture', FileType::class, ['required'=>false, 'data_class'=>null])
            ->add('priority',ChoiceType::class,[
                'choices'=> ['1'=>'1','2'=>'2','3'=>'3'],
                'expanded' =>false,
                'multiple' => false
                ])
            ->add('Valider', SubmitType::class,[
                'attr'=>[
                    'class' => 'btn-sm'
                    ]
                ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Product::class
        ));
    }
}
