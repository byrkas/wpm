<?php
namespace Application\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class ArtistRepository extends EntityRepository
{

    public function findOneByName($name)
    {
        $translit = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('a')
            ->from('Application\Entity\Artist', 'a')
            ->where('a.name like :name OR a.nameTranslit like :name')
            ->orWhere('a.name like :translit OR a.nameTranslit like :translit')
            ->setParameter('name',$name)
            ->setParameter('translit',$translit)
            ->setMaxResults(1);
        
        return $query->getQuery()->getOneOrNullResult();
    }
    
    public function findStrange()
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('a')
        ->from('Application\Entity\Artist', 'a')
        ->where('a.name like :name OR a.nameTranslit like :name')
        ->setParameter('name','%,%');
        
        return $query->getQuery()->getResult();
    }
}   