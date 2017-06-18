<?php


namespace AppBundle\Form\DataTransformer;

use AppBundle\Entity\Product;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PictureToStringTransformer implements DataTransformerInterface
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Transforms an object (picture) to a string (string).
     *
     * @param  Picture|null $picture
     * @return string
     */
    public function transform($picture)
    {
        if (null === $picture) {
            return '';
        }

        return $picture->getId();
    }
}