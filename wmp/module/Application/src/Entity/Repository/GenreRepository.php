<?php
namespace Application\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class GenreRepository extends EntityRepository
{
    public function getList()
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('g.id', 'g.title')
        ->from('Application\Entity\Genre', 'g');
    
        return $query->getQuery()->getArrayResult();
    }
    
    public function removeByIds($ids){
        $query = $this->getEntityManager()->createQueryBuilder();
        $res = $query->delete('Application\Entity\Genre', 'g')
        ->where('g.id in (:ids)')
        ->setParameter('ids',$ids)
        ->getQuery()
        ->execute();
    
        return $res;
    }
}   