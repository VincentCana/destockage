<?php

namespace DiscountBundle\Repository;

/**
 * ProductRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProductRepository extends \Doctrine\ORM\EntityRepository
{
    public function updatePicture($id, $title, $discountType, $updateDiscountValue, $newPrice)
    {
        return $this->createQueryBuilder('u')
            ->update('DiscountBundle\Entity\Product', 'u')
            ->set('u.title', '?1')
            ->set('u.discountType', '?2')
            ->set('u.discountValue', '?3')
            ->set('u.newPrice', '?4')
            ->setParameter(1, $title)
            ->setParameter(2, $discountType)
            ->setParameter(3, $updateDiscountValue)
            ->setParameter(4, $newPrice)
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }

    public function productVisibility($campLiveId)
    {
        return $this->createQueryBuilder('m')
                    ->where("m.productVisibility = ?1")
                    ->andWhere("m.campLiveId = ?2")
                    ->setParameter(1, 1)
                    ->setParameter(2, $campLiveId)
                    ->orderBy("m.dateWeek", "ASC")
                    ->getQuery()
                    ->getResult();
    }
    
    public function productConcactAlreadyExists($showConcatenation)
    {
        return $this->createQueryBuilder('m')
                    ->select('m.id')
                    ->where("m.concatenation = ?1")
                    ->setParameter(1, $showConcatenation)
                    ->andWhere('m.productVisibility = 1')
                    ->getQuery()
                    ->getResult();
    }

    public function productVisibilityToArray($campLiveId)
    {
        return $this->createQueryBuilder('m')
                    ->where("m.productVisibility = ?1")
                    ->andWhere("m.campLiveId = ?2")
                    ->setParameter(1, 1)
                    ->setParameter(2, $campLiveId)
                    ->orderBy("m.dateWeek", "ASC")
                    ->getQuery()
                    ->getArrayResult();
    }

    public function sumProductVisibility($campLiveId)
    {
        return $this->createQueryBuilder('m')
                    ->select('count(m.productVisibility)')
                    ->where("m.campLiveId = ?1")
                    ->andWhere('m.productVisibility = 1')
                    ->setParameter(1, $campLiveId)
                    ->getQuery()
                    ->getOneOrNullResult();
    }
}
