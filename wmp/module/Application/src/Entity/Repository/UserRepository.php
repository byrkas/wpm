<?php
namespace Application\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{

    public function getList()
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('u.id', 'u.email', 'u.showPromo', 'u.quotePromo', 'u.active', 'u.quoteExclusive', 'u.expireDate', 'u.comment', 'p.title as paymentPage')
            ->from('Application\Entity\User', 'u')
            ->leftJoin('u.PaymentPage', 'p');
        
        return $query->getQuery()->getArrayResult();
    }

    public function removeByIds($ids)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $res = $query->delete('Application\Entity\User', 'u')
            ->where('u.id in (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
        
        return $res;
    }

    public function clearFavorites($user)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $res = $query->delete('Application\Entity\Favorite', 'f')
            ->where('f.User = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
        
        return $res;
    }

    public function getDownloadedIds($user, $tracks = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('IDENTITY(d.Track) as id')
            ->from('Application\Entity\Download', 'd')
            ->where('d.User = :user')
            ->setParameter('user', $user);
        if(!empty($tracks)){
            $query->andWhere('d.Track in (:tracks)')->setParameter('tracks',$tracks);
        }
            
        $result = $query->getQuery()->getArrayResult();
        $data = [];
        foreach ($result as $entry){
            $data[] = $entry['id'];
        }
        
        return $data;
    }
}   