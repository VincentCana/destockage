<?php

namespace DiscountBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType as ProductTypeForm;
use DiscountBundle\Entity\Product;

class ProductType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

            ->add('title')
            //->add('picture', ProductTypeForm::class, ['label' => 'Photo du produit', 'data_class' => null])
            ->add('currentPrice')
            ->add('newPrice')
            //->add('quantity')
            ->add('discountType')
            ->add('discountValue')
            ->add('idProduct');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Product::class
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'discountbundle_product';
    }
}
