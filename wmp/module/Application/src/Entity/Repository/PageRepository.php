<?php
namespace Application\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class PageRepository extends EntityRepository
{
    public function getList()
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('p.id', 'p.title','p.isPublished','p.slug','p.isPayment')
        ->from('Application\Entity\Page', 'p');
    
        return $query->getQuery()->getArrayResult();
    }
    
    public function getListNotPayment()
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('p.id', 'p.title','p.isPublished','p.slug','p.isPayment')
        ->from('Application\Entity\Page', 'p')
        ->where('p.isPayment IS NULL OR p.isPayment = 0');
    
        return $query->getQuery()->getArrayResult();
    }
    
    public function getListPayment()
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('p.id', 'p.title','p.isPublished','p.slug','p.isPayment','p.isPaymentDefault')
        ->from('Application\Entity\Page', 'p')
        ->where('p.isPayment = 1');
    
        return $query->getQuery()->getArrayResult();
    }
    
    public function removeByIds($ids){
        $query = $this->getEntityManager()->createQueryBuilder();
        $res = $query->delete('Application\Entity\Page', 'p')
        ->where('p.id in (:ids)')
        ->setParameter('ids',$ids)
        ->getQuery()
        ->execute();
    
        return $res;
    }
    
    public function getPaymentPagesList()
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('p.id', 'p.title')
        ->from('Application\Entity\Page', 'p')
        ->where('p.isPayment = 1');
        
        $results = $query->getQuery()->getArrayResult();
        
        $data = [];
        foreach ($results as $entry){
            $data[$entry['id']] = $entry['title'];
        }
        return $data;
    }
    
    public function getPaymentByUser($user = null){
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('p.id', 'p.title', 'p.content')
        ->from('Application\Entity\Page', 'p')
        ->where('p.isPublished = 1 AND p.isPayment = 1')
        ->setMaxResults(1);
        
        if($user){
            $query
            ->leftJoin('Application\Entity\User','u','WITH','u.PaymentPage = p.id')
            ->andWhere('u.id = :user')->setParameter('user',$user);
        }else{
            $query->andWhere('p.isPaymentDefault = 1');
        }
        
        return $query->getQuery()->getOneOrNullResult();
    }
    
    public function getPageBySlug($slug){
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('p.id', 'p.title', 'p.content')
        ->from('Application\Entity\Page', 'p')
        ->where('p.isPublished = 1 AND p.slug = :slug')
        ->setParameter('slug', $slug)
        ->setMaxResults(1);        
        
        return $query->getQuery()->getOneOrNullResult();
    }
}   