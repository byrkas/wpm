<?php
namespace Application\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class BanIpRepository extends EntityRepository
{
    public function getList()
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('b.id', 'b.ip', 'b.created', 'b.updated')
        ->from('Application\Entity\BanIp', 'b');
    
        return $query->getQuery()->getArrayResult();
    }
    
    public function removeByIds($ids){
        $query = $this->getEntityManager()->createQueryBuilder();
        $res = $query->delete('Application\Entity\BanIp', 'b')
        ->where('b.id in (:ids)')
        ->setParameter('ids',$ids)
        ->getQuery()
        ->execute();
    
        return $res;
    }

    public function isBanned($ip)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('b.id')
        ->from('Application\Entity\BanIp', 'b')
        ->where('b.ip = :ip')
        ->setParameter('ip',$ip)
        ->setMaxResults(1);
    
        return ($query->getQuery()->getOneOrNullResult());
    }
    
    
}   