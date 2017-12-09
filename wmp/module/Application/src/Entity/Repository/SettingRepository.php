<?php
namespace Application\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class SettingRepository extends EntityRepository
{
    public function getList()
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('s.id', 's.code','s.value')
        ->from('Application\Entity\Setting', 's');
    
        return $query->getQuery()->getArrayResult();
    }
    
    public function checkMaintainMode()
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('s.value')
        ->from('Application\Entity\Setting', 's')
        ->where("s.code = 'maintain_mode'");
        
        return $query->getQuery()->getSingleScalarResult();
    }
    
    public function getSiteMode()
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('s.value')
        ->from('Application\Entity\Setting', 's')
        ->where("s.code = 'site_mode'");
        
        return $query->getQuery()->getSingleScalarResult();
    }
    
    public function getFooter()
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('s.value')
        ->from('Application\Entity\Setting', 's')
        ->where("s.code = 'footer'");
        
        return $query->getQuery()->getSingleScalarResult();
    }
}   