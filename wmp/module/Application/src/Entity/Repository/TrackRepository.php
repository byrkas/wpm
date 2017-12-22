<?php
namespace Application\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class TrackRepository extends EntityRepository
{

    public function getTotal($search = '')
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('COUNT(t.id) as cnt')
            ->from('Application\Entity\Track', 't')
            ->leftJoin('t.Artists', 'a')
            ->leftJoin('t.Label', 'l')
            ->leftJoin('t.Album', 'r')
            ->leftJoin('t.Genre', 'g');
        
        if (! empty($search)) {
            $query->andWhere('t.title like :search OR l.name like :search OR r.name like :search OR g.title like :search OR a.name like :search')->setParameter('search', '%' . $search . '%');
        }
        
        return $query->getQuery()->getSingleScalarResult();
    }

    public function getList($start, $limit, $orderBy, $order, $search = '')
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('t.id as id', 't.title as title', 't.publishDate as publishDate', "GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as artists", 't.fileType as fileType', "REPLACE(t.fileDestination, 'public/','/') as track", "REPLACE(t.wave, 'public/','/') as wave", "REPLACE(t.sampleDestination, 'public/','/') as sample", "REPLACE(t.cover, 'public/','/') as cover", 'l.name as label', 'r.name as album', 'g.title as genre', 'tt.name as type', 't.playtimeString', 't.isPublished', 'COUNT(DISTINCT d.id) as downloaded')
            ->from('Application\Entity\Track', 't')
            ->leftJoin('t.Artists', 'a')
            ->leftJoin('t.Label', 'l')
            ->leftJoin('t.Album', 'r')
            ->leftJoin('t.Genre', 'g')
            ->leftJoin('t.TrackType', 'tt')
            ->leftJoin('Application\Entity\Download', 'd', 'WITH', 'd.Track = t.id')
            ->groupBy('t.id')
            ->setFirstResult($start)
            ->setMaxResults($limit);
        
        if (! empty($order) && ! empty($orderBy)) {
            $query->addOrderBy($orderBy, $order);
        }
        if (! empty($search)) {
            $query->andWhere('t.title like :search OR l.name like :search OR r.name like :search OR g.title like :search OR a.name like :search')->setParameter('search', '%' . $search . '%');
        }
        
        return $query->getQuery()->getArrayResult();
    }

    public function removeByIds($ids)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $res = $query->delete('Application\Entity\Track', 't')
            ->where('t.id in (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
        
        return $res;
    }

    public function getDownloadsTotal($trackId, $search = '')
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('COUNT(d.id) as cnt')
            ->from('Application\Entity\Download', 'd')
            ->leftJoin('d.User', 'u')
            ->where('d.Track = :track')
            ->setParameter('track', $trackId);
        
        if (! empty($search)) {
            $query->andWhere('u.email like :search OR d.ip like :search')->setParameter('search', '%' . $search . '%');
        }
        
        return $query->getQuery()->getSingleScalarResult();
    }

    public function getDownloadsList($trackId, $start, $limit, $orderBy, $order, $search = '')
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('d.created as date', 'u.email as user', 'd.ip')
            ->from('Application\Entity\Download', 'd')
            ->leftJoin('d.User', 'u')
            ->where('d.Track = :track')
            ->setParameter('track', $trackId)
            ->setFirstResult($start)
            ->setMaxResults($limit);
        
        if (! empty($order) && ! empty($orderBy)) {
            $query->addOrderBy($orderBy, $order);
        }
        if (! empty($search)) {
            $query->andWhere('u.email like :search OR d.ip like :search')->setParameter('search', '%' . $search . '%');
        }
        
        return $query->getQuery()->getArrayResult();
    }

    public function publishAll()
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $res = $query->update('Application\Entity\Track', 't')
            ->set('t.isPublished', 1)
            ->where('t.isPublished != 1')
            ->getQuery()
            ->execute();
        
        return $res;
    }

    public function getSettingValue($code)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('s.value')
            ->from('Application\Entity\Setting', 's')
            ->where('s.code = :code')
            ->setParameter('code', $code);
        
        return $query->getQuery()->getSingleScalarResult();
    }

    public function checkTrackExist($title, $Label, $Artists)
    {
        $titleSimple = str_ireplace('(Original Mix)', '', $title);
        $titleSimple = str_ireplace('Original Mix', '', $titleSimple);
        
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('t')
            ->from('Application\Entity\Track', 't')
            ->where('t.title = :title OR t.title = :titleSimple')
            ->andWhere('t.Label = :label')
            ->setParameter('title', $title)
            ->setParameter('titleSimple', $titleSimple)
            ->setParameter('label', $Label)
            ->setMaxResults(1);
        
        $track = $query->getQuery()->getOneOrNullResult();
        if ($track) {
            $count = count($Artists);
            foreach ($Artists as $artist) {
                if ($track->getArtists()->contains($artist))
                    $count --;
            }
            return ($count == 0) ? $track : null;
        }
        
        return null;
    }

    public function searchLabel($search)
    {
        $result = $this->searchLabelFunc($search);
        if (! $result) {
            $result = $this->searchLabelFunc($search, true);
        }
        
        return $result;
    }

    public function searchLabelFunc($search, $simple = false)
    {
        if ($simple) {
            $templateValue = strtolower($this->getSettingValue('label'));
            $templateExploded = explode(';', $templateValue);
            $re = '/(.*\s)?(' . implode('|', $templateExploded) . ')(\s.*)?/i';
            preg_match_all($re, $search, $matches, PREG_SET_ORDER, 0);
            if (count($matches)) {
                $founded = $matches[0][2];
                $searchSimple = str_ireplace($founded, '', $search);
                $searchSimple = str_ireplace('  ', ' ', $searchSimple);
            } else {
                $searchSimple = $search;
            }
            $search = $searchSimple;
        }
        
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('l')
            ->from('Application\Entity\Label', 'l')
            ->where('l.name like :search')
            ->setParameter('search', '%' . $search . '%')
            ->setMaxResults(1);
        
        return $query->getQuery()->getOneOrNullResult();
    }

    public function searchArtists($search)
    {
        $templateValue = $this->getSettingValue('label');
        $templateExploded = explode(';', $templateValue);
        $searchSimple = str_ireplace($templateExploded, '', $search);
        $searchArr = [];
        foreach ($templateExploded as $tE) {
            $searchArr[] = $searchSimple . ' ' . ucfirst($tE);
        }
        
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('l')
            ->from('Application\Entity\Label', 'l')
            ->where('l.name in (:search)')
            ->setParameter('search', $searchArr);
        
        return $query->getQuery()->getOneOrNullResult();
    }

    public function getTypes($filter = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('COUNT(DISTINCT t.id) as cnt', 'tt.name', 'tt.id')
            ->from('Application\Entity\Track', 't')
            ->leftJoin('t.TrackType', 'tt')
            ->where('t.isPublished = 1')
            ->groupBy('tt.id')
            ->orderBy('cnt', 'DESC');
        
        if (isset($filter['trackIds'])) {
            $query->andWhere('t.id IN (:tracks)')->setParameter('tracks', $filter['trackIds']);
        }
        if (isset($filter['type'])) {
            $query->andWhere('t.TrackType = :type')->setParameter('type', $filter['type']);
        }
        if (isset($filter['artists'])) {
            $query->leftJoin('t.Artists', 'a')
                ->andWhere('a.id IN (:artists)')
                ->setParameter('artists', $filter['artists']);
        }
        if (isset($filter['label'])) {
            $query->andWhere('t.Label = :label')->setParameter('label', $filter['label']);
        }
        if (isset($filter['genre'])) {
            $query->andWhere('t.Genre = :genre')->setParameter('genre', $filter['genre']);
        }
        if (isset($filter['showPromo']) && (! $filter['showPromo'] || $filter['showPromo'] == 'false')) {
            $query->andWhere('t.TrackType != 2');
        }
        if (isset($filter['user'])) {
            if (isset($filter['favorites'])) {
                $query->leftJoin('Application\Entity\Favorite', 'd', 'WITH', 'd.Track = t.id AND d.User = :user')
                    ->andWhere('d.id IS NOT NULL')
                    ->setParameter('user', $filter['user']);
            } else {
                $query->leftJoin('Application\Entity\Download', 'd', 'WITH', 'd.Track = t.id AND d.User = :user')
                    ->andWhere('d.id IS NOT NULL')
                    ->setParameter('user', $filter['user']);
            }
        }
        if (isset($filter['start'])) {
            $query->andWhere('t.publishDate >= :start')->setParameter('start', $filter['start'] . ' 00:00');
        }
        if (isset($filter['end'])) {
            $query->andWhere('t.publishDate <= :end')->setParameter('end', $filter['end'] . ' 23:59');
        }
        if (isset($filter['wav'])) {
            $query->andWhere("t.fileType = 'audio/x-wave'");
        }
        if (isset($filter['showPromo']) && (! $filter['showPromo'] || $filter['showPromo'] == 'false')) {
            $query->andWhere('t.TrackType != 2');
        }
        if (isset($filter['last'])) {
            $today = new \DateTime();
            $yesterday = new \DateTime('yesterday');
            $week = new \DateTime('7 days ago');
            $month = new \DateTime('7 days ago');
            
            switch ($filter['last']) {
                case '0d':
                    $query->andWhere('DATE(t.publishDate) = :today')->setParameter('today', $today->format('Y-m-d'));
                    break;
                case '1d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                    ->setParameter('startB', $yesterday->format('Y-m-d') . ' 00:00')
                    ->setParameter('endB', $yesterday->format('Y-m-d') . ' 23:59');
                    break;
                case '7d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                    ->setParameter('startB', $week->format('Y-m-d') . ' 00:00')
                    ->setParameter('endB', $today->format('Y-m-d') . ' 23:59');
                    break;
                case '30d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                    ->setParameter('startB', $month->format('Y-m-d') . ' 00:00')
                    ->setParameter('endB', $today->format('Y-m-d') . ' 23:59');
                    break;
            }
        }
        
        return $query->getQuery()->getArrayResult();
    }

    public function getGenres($filter = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('COUNT(DISTINCT t.id) as cnt', 'g.title as name', 'g.id')
            ->from('Application\Entity\Track', 't')
            ->leftJoin('t.Genre', 'g')
            ->where('t.isPublished = 1')
            ->groupBy('g.id')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults(50);
        
        if (isset($filter['trackIds'])) {
            $query->andWhere('t.id IN (:tracks)')->setParameter('tracks', $filter['trackIds']);
        }
        if (isset($filter['type'])) {
            $query->andWhere('t.TrackType = :type')->setParameter('type', $filter['type']);
        }
        if (isset($filter['artists'])) {
            $query->leftJoin('t.Artists', 'a')
                ->andWhere('a.id IN (:artists)')
                ->setParameter('artists', $filter['artists']);
        }
        if (isset($filter['label'])) {
            $query->andWhere('t.Label = :label')->setParameter('label', $filter['label']);
        }
        if (isset($filter['genre'])) {
            $query->andWhere('t.Genre = :genre')->setParameter('genre', $filter['genre']);
        }
        if (isset($filter['user'])) {
            if (isset($filter['favorites'])) {
                $query->leftJoin('Application\Entity\Favorite', 'd', 'WITH', 'd.Track = t.id AND d.User = :user')
                    ->andWhere('d.id IS NOT NULL')
                    ->setParameter('user', $filter['user']);
            } else {
                $query->leftJoin('Application\Entity\Download', 'd', 'WITH', 'd.Track = t.id AND d.User = :user')
                    ->andWhere('d.id IS NOT NULL')
                    ->setParameter('user', $filter['user']);
            }
        }
        if (isset($filter['start'])) {
            $query->andWhere('t.publishDate >= :start')->setParameter('start', $filter['start'] . ' 00:00');
        }
        if (isset($filter['end'])) {
            $query->andWhere('t.publishDate <= :end')->setParameter('end', $filter['end'] . ' 23:59');
        }
        if (isset($filter['wav'])) {
            $query->andWhere("t.fileType = 'audio/x-wave'");
        }
        if (isset($filter['showPromo']) && (! $filter['showPromo'] || $filter['showPromo'] == 'false')) {
            $query->andWhere('t.TrackType != 2');
        }
        if (isset($filter['last'])) {
            $today = new \DateTime();
            $yesterday = new \DateTime('yesterday');
            $week = new \DateTime('7 days ago');
            $month = new \DateTime('7 days ago');
            
            switch ($filter['last']) {
                case '0d':
                    $query->andWhere('DATE(t.publishDate) = :today')->setParameter('today', $today->format('Y-m-d'));
                    break;
                case '1d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                    ->setParameter('startB', $yesterday->format('Y-m-d') . ' 00:00')
                    ->setParameter('endB', $yesterday->format('Y-m-d') . ' 23:59');
                    break;
                case '7d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                    ->setParameter('startB', $week->format('Y-m-d') . ' 00:00')
                    ->setParameter('endB', $today->format('Y-m-d') . ' 23:59');
                    break;
                case '30d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                    ->setParameter('startB', $month->format('Y-m-d') . ' 00:00')
                    ->setParameter('endB', $today->format('Y-m-d') . ' 23:59');
                    break;
            }
        }
        
        return $query->getQuery()->getArrayResult();
    }

    public function getLabels($filter = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('COUNT(DISTINCT t.id) as cnt', 'l.name', 'l.id')
            ->from('Application\Entity\Track', 't')
            ->leftJoin('t.Label', 'l')
            ->where('t.isPublished = 1')
            ->groupBy('l.id')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults(50);
        
        if (isset($filter['trackIds'])) {
            $query->andWhere('t.id IN (:tracks)')->setParameter('tracks', $filter['trackIds']);
        }
        if (isset($filter['labels'])) {
            $query->andWhere('l.id IN (:labels)')->setParameter('labels', $filter['labels']);
        }
        if (isset($filter['type'])) {
            $query->andWhere('t.TrackType = :type')->setParameter('type', $filter['type']);
        }
        if (isset($filter['artists'])) {
            $query->leftJoin('t.Artists', 'a')
                ->andWhere('a.id IN (:artists)')
                ->setParameter('artists', $filter['artists']);
        }
        if (isset($filter['label'])) {
            $query->andWhere('t.Label = :label')->setParameter('label', $filter['label']);
        }
        if (isset($filter['genre'])) {
            $query->andWhere('t.Genre = :genre')->setParameter('genre', $filter['genre']);
        }
        if (isset($filter['user'])) {
            if (isset($filter['favorites'])) {
                $query->leftJoin('Application\Entity\Favorite', 'd', 'WITH', 'd.Track = t.id AND d.User = :user')
                    ->andWhere('d.id IS NOT NULL')
                    ->setParameter('user', $filter['user']);
            } else {
                $query->leftJoin('Application\Entity\Download', 'd', 'WITH', 'd.Track = t.id AND d.User = :user')
                    ->andWhere('d.id IS NOT NULL')
                    ->setParameter('user', $filter['user']);
            }
        }
        if (isset($filter['start'])) {
            $query->andWhere('t.publishDate >= :start')->setParameter('start', $filter['start'] . ' 00:00');
        }
        if (isset($filter['end'])) {
            $query->andWhere('t.publishDate <= :end')->setParameter('end', $filter['end'] . ' 23:59');
        }
        if (isset($filter['wav'])) {
            $query->andWhere("t.fileType = 'audio/x-wave'");
        }
        if (isset($filter['showPromo']) && (! $filter['showPromo'] || $filter['showPromo'] == 'false')) {
            $query->andWhere('t.TrackType != 2');
        }
        if (isset($filter['last'])) {
            $today = new \DateTime();
            $yesterday = new \DateTime('yesterday');
            $week = new \DateTime('7 days ago');
            $month = new \DateTime('7 days ago');
            
            switch ($filter['last']) {
                case '0d':
                    $query->andWhere('DATE(t.publishDate) = :today')->setParameter('today', $today->format('Y-m-d'));
                    break;
                case '1d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                    ->setParameter('startB', $yesterday->format('Y-m-d') . ' 00:00')
                    ->setParameter('endB', $yesterday->format('Y-m-d') . ' 23:59');
                    break;
                case '7d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                    ->setParameter('startB', $week->format('Y-m-d') . ' 00:00')
                    ->setParameter('endB', $today->format('Y-m-d') . ' 23:59');
                    break;
                case '30d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                    ->setParameter('startB', $month->format('Y-m-d') . ' 00:00')
                    ->setParameter('endB', $today->format('Y-m-d') . ' 23:59');
                    break;
            }
        }
        
        return $query->getQuery()->getArrayResult();
    }

    public function getArtists($filter = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('COUNT(DISTINCT t.id) as cnt', 'a.name', 'a.id')
            ->from('Application\Entity\Track', 't')
            ->leftJoin('t.Artists', 'a')
            ->where('t.isPublished = 1')
            ->groupBy('a.id')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults(50);
        
        if (isset($filter['trackIds'])) {
            $query->andWhere('t.id IN (:tracks)')->setParameter('tracks', $filter['trackIds']);
        }
        if (isset($filter['type'])) {
            $query->andWhere('t.TrackType = :type')->setParameter('type', $filter['type']);
        }
        if (isset($filter['artists'])) {
            $query->leftJoin('t.Artists', 'aa')
                ->andWhere('aa.id IN (:artists)')
                ->setParameter('artists', $filter['artists']);
        }
        if (isset($filter['label'])) {
            $query->andWhere('t.Label = :label')->setParameter('label', $filter['label']);
        }
        if (isset($filter['genre'])) {
            $query->andWhere('t.Genre = :genre')->setParameter('genre', $filter['genre']);
        }
        if (isset($filter['user'])) {
            if (isset($filter['favorites'])) {
                $query->leftJoin('Application\Entity\Favorite', 'd', 'WITH', 'd.Track = t.id AND d.User = :user')
                    ->andWhere('d.id IS NOT NULL')
                    ->setParameter('user', $filter['user']);
            } else {
                $query->leftJoin('Application\Entity\Download', 'd', 'WITH', 'd.Track = t.id AND d.User = :user')
                    ->andWhere('d.id IS NOT NULL')
                    ->setParameter('user', $filter['user']);
            }
        }
        if (isset($filter['start'])) {
            $query->andWhere('t.publishDate >= :start')->setParameter('start', $filter['start'] . ' 00:00');
        }
        if (isset($filter['end'])) {
            $query->andWhere('t.publishDate <= :end')->setParameter('end', $filter['end'] . ' 23:59');
        }
        if (isset($filter['wav'])) {
            $query->andWhere("t.fileType = 'audio/x-wave'");
        }
        if (isset($filter['showPromo']) && (! $filter['showPromo'] || $filter['showPromo'] == 'false')) {
            $query->andWhere('t.TrackType != 2');
        }
        if (isset($filter['last'])) {
            $today = new \DateTime();
            $yesterday = new \DateTime('yesterday');
            $week = new \DateTime('7 days ago');
            $month = new \DateTime('7 days ago');
            
            switch ($filter['last']) {
                case '0d':
                    $query->andWhere('DATE(t.publishDate) = :today')->setParameter('today', $today->format('Y-m-d'));
                    break;
                case '1d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                    ->setParameter('startB', $yesterday->format('Y-m-d') . ' 00:00')
                    ->setParameter('endB', $yesterday->format('Y-m-d') . ' 23:59');
                    break;
                case '7d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                    ->setParameter('startB', $week->format('Y-m-d') . ' 00:00')
                    ->setParameter('endB', $today->format('Y-m-d') . ' 23:59');
                    break;
                case '30d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                    ->setParameter('startB', $month->format('Y-m-d') . ' 00:00')
                    ->setParameter('endB', $today->format('Y-m-d') . ' 23:59');
                    break;
            }
        }
        
        return $query->getQuery()->getArrayResult();
    }

    public function getTracks($limit, $start, $filter = [], $sortBy = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('t.id', 't.title as title', 't.publishDate as release', 'GROUP_CONCAT(a.name) as artists', 't.playtimeString as length', "REPLACE(t.wave, 'public/','/') as wave", 't.fileFormat', "REPLACE(t.sampleDestination, 'public/','/') as sample", "REPLACE(t.cover, 'public/','/') as cover", 'l.name as label', 'l.id as labelId', 'r.name as album', 'r.id as albumId', 'g.title as genre', 'g.id as genreId', 'tt.name as type', 'tt.id as typeId')
            ->from('Application\Entity\Track', 't')
            ->leftJoin('t.Artists', 'a')
            ->leftJoin('t.Label', 'l')
            ->leftJoin('t.Album', 'r')
            ->leftJoin('t.Genre', 'g')
            ->leftJoin('t.TrackType', 'tt')
            ->where('t.isPublished = 1')
            ->groupBy('t.id')
            ->setMaxResults($limit)
            ->setFirstResult($start);
        if (! empty($sortBy)) {
            $query->orderBy($sortBy[0], $sortBy[1]);
        }
        
        if (isset($filter['search'])) {
            $query->andWhere('t.title like :search OR l.name like :search OR r.name like :search OR a.name like :search')->setParameter('search', '%' . $filter['search'] . '%');
        }
        if (isset($filter['type'])) {
            $query->andWhere('tt.id = :type')->setParameter('type', $filter['type']);
        }
        if (isset($filter['artists'])) {
            $query->andWhere('a.id IN (:artists)')->setParameter('artists', $filter['artists']);
        }
        if (isset($filter['label'])) {
            $query->andWhere('l.id = :label')->setParameter('label', $filter['label']);
        }
        if (isset($filter['genre'])) {
            $query->andWhere('g.id = :genre')->setParameter('genre', $filter['genre']);
        }
        if (isset($filter['album'])) {
            $query->andWhere('r.id = :album')->setParameter('album', $filter['album']);
        }
        if (isset($filter['start'])) {
            $query->andWhere('t.publishDate >= :start')->setParameter('start', $filter['start'] . ' 00:00');
        }
        if (isset($filter['end'])) {
            $query->andWhere('t.publishDate <= :end')->setParameter('end', $filter['end'] . ' 23:59');
        }
        if (isset($filter['wav'])) {
            $query->andWhere("t.fileType = 'audio/x-wave'");
        }
        if (isset($filter['showPromo']) && (! $filter['showPromo'] || $filter['showPromo'] == 'false')) {
            $query->andWhere('t.TrackType != 2');
        }
        if (isset($filter['last'])) {
            $today = new \DateTime();
            $yesterday = new \DateTime('yesterday');
            $week = new \DateTime('7 days ago');
            $month = new \DateTime('7 days ago');
            
            switch ($filter['last']) {
                case '0d':
                    $query->andWhere('DATE(t.publishDate) = :today')->setParameter('today', $today->format('Y-m-d'));
                    break;
                case '1d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                        ->setParameter('startB', $yesterday->format('Y-m-d') . ' 00:00')
                        ->setParameter('endB', $yesterday->format('Y-m-d') . ' 23:59');
                    break;
                case '7d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                        ->setParameter('startB', $week->format('Y-m-d') . ' 00:00')
                        ->setParameter('endB', $today->format('Y-m-d') . ' 23:59');
                    break;
                case '30d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                        ->setParameter('startB', $month->format('Y-m-d') . ' 00:00')
                        ->setParameter('endB', $today->format('Y-m-d') . ' 23:59');
                    break;
            }
        }
        
        return $query->getQuery()->getArrayResult();
    }

    public function getTrackIds($limit, $start, $filter = [], $sortBy = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('t.id', 't.title as title', 't.publishDate as release', 'GROUP_CONCAT(a.name) as artists', 't.fileFormat', 'l.name as label', 'l.id as labelId', 'r.name as album', 'r.id as albumId', 'g.title as genre', 'g.id as genreId', 'tt.name as type', 'tt.id as typeId')
            ->from('Application\Entity\Track', 't')
            ->leftJoin('t.Artists', 'a')
            ->leftJoin('t.Label', 'l')
            ->leftJoin('t.Album', 'r')
            ->leftJoin('t.Genre', 'g')
            ->leftJoin('t.TrackType', 'tt')
            ->where('t.isPublished = 1')
            ->groupBy('t.id')
            ->setMaxResults($limit)
            ->setFirstResult($start);
        if (! empty($sortBy)) {
            $query->orderBy($sortBy[0], $sortBy[1]);
        }
        
        if (isset($filter['search'])) {
            $query->andWhere('t.title like :search OR l.name like :search OR r.name like :search')->setParameter('search', '%' . $filter['search'] . '%');
        }
        if (isset($filter['type'])) {
            $query->andWhere('tt.id = :type')->setParameter('type', $filter['type']);
        }
        if (isset($filter['artists'])) {
            $query->andWhere('a.id IN (:artists)')->setParameter('artists', $filter['artists']);
        }
        if (isset($filter['label'])) {
            $query->andWhere('l.id = :label')->setParameter('label', $filter['label']);
        }
        if (isset($filter['genre'])) {
            $query->andWhere('g.id = :genre')->setParameter('genre', $filter['genre']);
        }
        if (isset($filter['album'])) {
            $query->andWhere('r.id = :album')->setParameter('album', $filter['album']);
        }
        if (isset($filter['start'])) {
            $query->andWhere('t.publishDate >= :start')->setParameter('start', $filter['start'] . ' 00:00');
        }
        if (isset($filter['end'])) {
            $query->andWhere('t.publishDate <= :end')->setParameter('end', $filter['end'] . ' 23:59');
        }
        if (isset($filter['wav'])) {
            $query->andWhere("t.fileType = 'audio/x-wave'");
        }
        if (isset($filter['showPromo']) && (! $filter['showPromo'] || $filter['showPromo'] == 'false')) {
            $query->andWhere('t.TrackType != 2');
        }
        if (isset($filter['last'])) {
            $today = new \DateTime();
            $yesterday = new \DateTime('yesterday');
            $week = new \DateTime('7 days ago');
            $month = new \DateTime('7 days ago');
            
            switch ($filter['last']) {
                case '0d':
                    $query->andWhere('DATE(t.publishDate) = :today')->setParameter('today', $today->format('Y-m-d'));
                    break;
                case '1d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                        ->setParameter('startB', $yesterday->format('Y-m-d') . ' 00:00')
                        ->setParameter('endB', $yesterday->format('Y-m-d') . ' 23:59');
                    break;
                case '7d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                        ->setParameter('startB', $week->format('Y-m-d') . ' 00:00')
                        ->setParameter('endB', $today->format('Y-m-d') . ' 23:59');
                    break;
                case '30d':
                    $query->andWhere('t.publishDate BETWEEN :startB AND :endB')
                        ->setParameter('startB', $month->format('Y-m-d') . ' 00:00')
                        ->setParameter('endB', $today->format('Y-m-d') . ' 23:59');
                    break;
            }
        }
        
        $result = $query->getQuery()->getArrayResult();
        $ids = [];
        
        foreach ($result as $entry) {
            $ids[] = $entry['id'];
        }
        return $ids;
    }

    public function getTotalTracks($filter = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('COUNT(t.id)')
            ->from('Application\Entity\Track', 't')
            ->where('t.isPublished = 1')
            ->setMaxResults(1);
        
        if (isset($filter['search'])) {
            $query->leftJoin('t.Artists', 'a')
                ->leftJoin('t.Label', 'l')
                ->leftJoin('t.Album', 'al')
                ->andWhere('t.title like :search OR l.name like :search OR al.name like :search OR a.name like :search')
                ->setParameter('search', '%' . $filter['search'] . '%');
        }
        
        if (isset($filter['type'])) {
            $query->andWhere('t.TrackType = :type')->setParameter('type', $filter['type']);
        }
        if (isset($filter['artists'])) {
            $query->leftJoin('t.Artists', 'a')
                ->andWhere('a.id IN (:artists)')
                ->setParameter('artists', $filter['artists']);
        }
        if (isset($filter['label'])) {
            $query->andWhere('t.Label = :label')->setParameter('label', $filter['label']);
        }
        if (isset($filter['genre'])) {
            $query->andWhere('t.Genre = :genre')->setParameter('genre', $filter['genre']);
        }
        if (isset($filter['album'])) {
            $query->andWhere('r.id = :album')->setParameter('album', $filter['album']);
        }
        if (isset($filter['showPromo']) && (! $filter['showPromo'] || $filter['showPromo'] == 'false')) {
            $query->andWhere('t.TrackType != 2');
        }
        if (isset($filter['start'])) {
            $query->andWhere('t.publishDate >= :start')->setParameter('start', $filter['start'] . ' 00:00');
        }
        if (isset($filter['end'])) {
            $query->andWhere('t.publishDate <= :end')->setParameter('end', $filter['end'] . ' 23:59');
        }
        if (isset($filter['wav'])) {
            $query->andWhere("t.fileType = 'audio/x-wave'");
        }
        if (isset($filter['last'])) {
            $today = new \DateTime();
            $yesterday = new \DateTime('yesterday');
            $week = new \DateTime('7 days ago');
            $month = new \DateTime('7 days ago');
            
            switch ($filter['last']) {
                case '0d':
                    $query->andWhere('DATE(t.publishDate) = :today')->setParameter('today', $today->format('Y-m-d'));
                    break;
                case '1d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                        ->setParameter('start', $yesterday->format('Y-m-d') . ' 00:00')
                        ->setParameter('end', $yesterday->format('Y-m-d') . ' 23:59');
                    break;
                case '7d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                        ->setParameter('start', $week->format('Y-m-d') . ' 00:00')
                        ->setParameter('end', $today->format('Y-m-d') . ' 23:59');
                    break;
                case '30d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                        ->setParameter('start', $month->format('Y-m-d') . ' 00:00')
                        ->setParameter('end', $today->format('Y-m-d') . ' 23:59');
                    break;
            }
        }
        
        return $query->getQuery()->getSingleScalarResult();
    }

    public function getTracksDownloaded($user, $limit, $start, $filter = [], $sortBy = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('t.id', 't.title as title', 't.publishDate as release', 'GROUP_CONCAT(a.name) as artists', 't.playtimeString as length', "REPLACE(t.wave, 'public/','/') as wave", 't.fileFormat', "REPLACE(t.sampleDestination, 'public/','/') as sample", "REPLACE(t.cover, 'public/','/') as cover", 'l.name as label', 'l.id as labelId', 'r.name as album', 'r.id as albumId', 'g.title as genre', 'g.id as genreId', 'tt.name as type', 'd.created')
            ->from('Application\Entity\Download', 'd')
            ->leftJoin('d.Track', 't')
            ->leftJoin('t.Artists', 'a')
            ->leftJoin('t.Label', 'l')
            ->leftJoin('t.Album', 'r')
            ->leftJoin('t.Genre', 'g')
            ->leftJoin('t.TrackType', 'tt')
            ->where('d.User = :user')
            ->setParameter('user', $user)
            ->groupBy('t.id')
            ->setMaxResults($limit)
            ->setFirstResult($start);
        
        if (! empty($sortBy)) {
            if ($sortBy[0] == 'created')
                $sortBy[0] = 'd.created';
            $query->orderBy($sortBy[0], $sortBy[1]);
        }
        
        if (isset($filter['type'])) {
            $query->andWhere('tt.id = :type')->setParameter('type', $filter['type']);
        }
        if (isset($filter['artists'])) {
            $query->andWhere('a.id IN (:artists)')->setParameter('artists', $filter['artists']);
        }
        if (isset($filter['label'])) {
            $query->andWhere('l.id = :label')->setParameter('label', $filter['label']);
        }
        if (isset($filter['genre'])) {
            $query->andWhere('g.id = :genre')->setParameter('genre', $filter['genre']);
        }
        if (isset($filter['album'])) {
            $query->andWhere('r.id = :album')->setParameter('album', $filter['album']);
        }
        if (isset($filter['wav'])) {
            $query->andWhere("t.fileType = 'audio/x-wave'");
        }
        if (isset($filter['start'])) {
            $query->andWhere('t.publishDate >= :start')->setParameter('start', $filter['start'] . ' 00:00');
        }
        if (isset($filter['end'])) {
            $query->andWhere('t.publishDate <= :end')->setParameter('end', $filter['end'] . ' 23:59');
        }
        if (isset($filter['last'])) {
            $today = new \DateTime();
            $yesterday = new \DateTime('yesterday');
            $week = new \DateTime('7 days ago');
            $month = new \DateTime('7 days ago');
            
            switch ($filter['last']) {
                case '0d':
                    $query->andWhere('DATE(t.publishDate) = :today')->setParameter('today', $today->format('Y-m-d'));
                    break;
                case '1d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                        ->setParameter('start', $yesterday->format('Y-m-d') . ' 00:00')
                        ->setParameter('end', $yesterday->format('Y-m-d') . ' 23:59');
                    break;
                case '7d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                        ->setParameter('start', $week->format('Y-m-d') . ' 00:00')
                        ->setParameter('end', $today->format('Y-m-d') . ' 23:59');
                    break;
                case '30d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                        ->setParameter('start', $month->format('Y-m-d') . ' 00:00')
                        ->setParameter('end', $today->format('Y-m-d') . ' 23:59');
                    break;
            }
        }
        
        return $query->getQuery()->getArrayResult();
    }

    public function getTotalTracksDownloaded($user, $filter = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select("COUNT(DISTINCT d.Track)")
            ->from('Application\Entity\Download', 'd')
            ->leftJoin('d.Track', 't')
            ->where('d.User = :user')
            ->setParameter('user', $user);
        
        if (isset($filter['type'])) {
            $query->andWhere('t.TrackType = :type')->setParameter('type', $filter['type']);
        }
        if (isset($filter['artists'])) {
            $query->leftJoin('t.Artists', 'a')
                ->andWhere('a.id IN (:artists)')
                ->setParameter('artists', $filter['artists']);
        }
        if (isset($filter['label'])) {
            $query->andWhere('t.Label = :label')->setParameter('label', $filter['label']);
        }
        if (isset($filter['genre'])) {
            $query->andWhere('t.Genre = :genre')->setParameter('genre', $filter['genre']);
        }
        if (isset($filter['wav'])) {
            $query->andWhere("t.fileType = 'audio/x-wave'");
        }
        if (isset($filter['start'])) {
            $query->andWhere('t.publishDate >= :start')->setParameter('start', $filter['start'] . ' 00:00');
        }
        if (isset($filter['end'])) {
            $query->andWhere('t.publishDate <= :end')->setParameter('end', $filter['end'] . ' 23:59');
        }
        if (isset($filter['last'])) {
            $today = new \DateTime();
            $yesterday = new \DateTime('yesterday');
            $week = new \DateTime('7 days ago');
            $month = new \DateTime('7 days ago');
            
            switch ($filter['last']) {
                case '0d':
                    $query->andWhere('DATE(t.publishDate) = :today')->setParameter('today', $today->format('Y-m-d'));
                    break;
                case '1d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                        ->setParameter('start', $yesterday->format('Y-m-d') . ' 00:00')
                        ->setParameter('end', $yesterday->format('Y-m-d') . ' 23:59');
                    break;
                case '7d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                        ->setParameter('start', $week->format('Y-m-d') . ' 00:00')
                        ->setParameter('end', $today->format('Y-m-d') . ' 23:59');
                    break;
                case '30d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                        ->setParameter('start', $month->format('Y-m-d') . ' 00:00')
                        ->setParameter('end', $today->format('Y-m-d') . ' 23:59');
                    break;
            }
        }
        
        return $query->getQuery()->getSingleScalarResult();
    }

    public function getDownloadedForArchive($user, $limit, $start, $filter = [], $sortBy = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('t.id', 't.fileDestination', 't.publishDate as release','t.title', 't.fileSize', 't.crc32', 'l.name as label', "GROUP_CONCAT(a.name SEPARATOR ', ') as artists")
            ->from('Application\Entity\Download', 'd')
            ->leftJoin('d.Track', 't')
            ->leftJoin('t.Artists', 'a')
            ->leftJoin('t.Label', 'l')
            ->where('d.User = :user')
            ->setParameter('user', $user)
            ->groupBy('t.id')
            ->setMaxResults($limit)
            ->setFirstResult($start);
        
        if (! empty($sortBy)) {
            if ($sortBy[0] == 'created')
                $sortBy[0] = 'd.created';
            $query->orderBy($sortBy[0], $sortBy[1]);
        }
        
        if (isset($filter['type'])) {
            $query->andWhere('t.TrackType = :type')->setParameter('type', $filter['type']);
        }
        if (isset($filter['artists'])) {
            $query->andWhere('a.id IN (:artists)')->setParameter('artists', $filter['artists']);
        }
        if (isset($filter['label'])) {
            $query->andWhere('l.id = :label')->setParameter('label', $filter['label']);
        }
        if (isset($filter['genre'])) {
            $query->leftJoin('t.Genre', 'g')
                ->andWhere('g.id = :genre')
                ->setParameter('genre', $filter['genre']);
        }
        if (isset($filter['album'])) {
            $query->leftJoin('t.Album', 'r')
                ->andWhere('r.id = :album')
                ->setParameter('album', $filter['album']);
        }
        if (isset($filter['wav'])) {
            $query->andWhere("t.fileType = 'audio/x-wave'");
        }
        if (isset($filter['start'])) {
            $query->andWhere('t.publishDate >= :start')->setParameter('start', $filter['start'] . ' 00:00');
        }
        if (isset($filter['end'])) {
            $query->andWhere('t.publishDate <= :end')->setParameter('end', $filter['end'] . ' 23:59');
        }
        if (isset($filter['last'])) {
            $today = new \DateTime();
            $yesterday = new \DateTime('yesterday');
            $week = new \DateTime('7 days ago');
            $month = new \DateTime('7 days ago');
            
            switch ($filter['last']) {
                case '0d':
                    $query->andWhere('DATE(t.publishDate) = :today')->setParameter('today', $today->format('Y-m-d'));
                    break;
                case '1d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                    ->setParameter('start', $yesterday->format('Y-m-d') . ' 00:00')
                    ->setParameter('end', $yesterday->format('Y-m-d') . ' 23:59');
                    break;
                case '7d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                    ->setParameter('start', $week->format('Y-m-d') . ' 00:00')
                    ->setParameter('end', $today->format('Y-m-d') . ' 23:59');
                    break;
                case '30d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                    ->setParameter('start', $month->format('Y-m-d') . ' 00:00')
                    ->setParameter('end', $today->format('Y-m-d') . ' 23:59');
                    break;
            }
        }
        
        return $query->getQuery()->getArrayResult();
    }

    public function getTracksForArchive($limit, $start, $filter = [], $sortBy = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('t.id', 't.fileDestination', 't.title', 't.fileSize', 't.crc32', 'l.name as label', "GROUP_CONCAT(a.name SEPARATOR ', ') as artists", 'tt.name as type')
            ->from('Application\Entity\Track', 't')
            ->leftJoin('t.Artists', 'a')
            ->leftJoin('t.TrackType', 'tt')
            ->leftJoin('t.Label', 'l')
            ->groupBy('t.id')
            ->setMaxResults($limit)
            ->setFirstResult($start);
        
        if (isset($filter['trackIds']) && ! empty($filter['trackIds'])) {
            $query->andWhere('t.id IN (:ids)')->setParameter('ids', $filter['trackIds']);
        } else {
            if (isset($filter['album'])) {
                $query->andWhere('t.Album = :album')->setParameter('album', $filter['album']);
            }
            if (isset($filter['type'])) {
                $query->andWhere('tt.id = :type')->setParameter('type', $filter['type']);
            }
            if (isset($filter['artists'])) {
                $query->leftJoin('t.Artists', 'a')
                    ->andWhere('a.id IN (:artists)')
                    ->setParameter('artists', $filter['artists']);
            }
            if (isset($filter['label'])) {
                $query->andWhere('t.Label = :label')->setParameter('label', $filter['label']);
            }
            if (isset($filter['genre'])) {
                $query->andWhere('t.Genre = :genre')->setParameter('genre', $filter['genre']);
            }
            if (isset($filter['showPromo']) && (! $filter['showPromo'] || $filter['showPromo'] == 'false')) {
                $query->andWhere('t.TrackType != 2');
            }
        }
        
        return $query->getQuery()->getArrayResult();
    }

    public function getTracksFavorites($user, $limit, $start, $filter = [], $sortBy = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('t.id', 't.title as title', 't.publishDate as release', "GROUP_CONCAT(a.name SEPARATOR ', ') as artists", 't.playtimeString as length', "REPLACE(t.wave, 'public/','/') as wave", 't.fileFormat', "REPLACE(t.sampleDestination, 'public/','/') as sample", "REPLACE(t.cover, 'public/','/') as cover", 'l.name as label', 'l.id as labelId', 'r.name as album', 'r.id as albumId', 'g.title as genre', 'g.id as genreId', 'tt.name as type', 'f.created')
            ->from('Application\Entity\Favorite', 'f')
            ->leftJoin('f.Track', 't')
            ->leftJoin('t.Artists', 'a')
            ->leftJoin('t.Label', 'l')
            ->leftJoin('t.Album', 'r')
            ->leftJoin('t.Genre', 'g')
            ->leftJoin('t.TrackType', 'tt')
            ->where('f.User = :user')
            ->setParameter('user', $user)
            ->groupBy('t.id')
            ->setMaxResults($limit)
            ->setFirstResult($start);
        
        if (! empty($sortBy)) {
            if ($sortBy[0] == 'created')
                $sortBy[0] = 'f.created';
            $query->orderBy($sortBy[0], $sortBy[1]);
        }
        
        if (isset($filter['type'])) {
            $query->andWhere('tt.id = :type')->setParameter('type', $filter['type']);
        }
        if (isset($filter['artists'])) {
            $query->andWhere('a.id IN (:artists)')->setParameter('artists', $filter['artists']);
        }
        if (isset($filter['label'])) {
            $query->andWhere('l.id = :label')->setParameter('label', $filter['label']);
        }
        if (isset($filter['genre'])) {
            $query->andWhere('g.id = :genre')->setParameter('genre', $filter['genre']);
        }
        if (isset($filter['album'])) {
            $query->andWhere('r.id = :album')->setParameter('album', $filter['album']);
        }
        if (isset($filter['wav'])) {
            $query->andWhere("t.fileType = 'audio/x-wave'");
        }
        if (isset($filter['start'])) {
            $query->andWhere('t.publishDate >= :start')->setParameter('start', $filter['start'] . ' 00:00');
        }
        if (isset($filter['end'])) {
            $query->andWhere('t.publishDate <= :end')->setParameter('end', $filter['end'] . ' 23:59');
        }
        if (isset($filter['last'])) {
            $today = new \DateTime();
            $yesterday = new \DateTime('yesterday');
            $week = new \DateTime('7 days ago');
            $month = new \DateTime('7 days ago');
            
            switch ($filter['last']) {
                case '0d':
                    $query->andWhere('DATE(t.publishDate) = :today')->setParameter('today', $today->format('Y-m-d'));
                    break;
                case '1d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                    ->setParameter('start', $yesterday->format('Y-m-d') . ' 00:00')
                    ->setParameter('end', $yesterday->format('Y-m-d') . ' 23:59');
                    break;
                case '7d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                    ->setParameter('start', $week->format('Y-m-d') . ' 00:00')
                    ->setParameter('end', $today->format('Y-m-d') . ' 23:59');
                    break;
                case '30d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                    ->setParameter('start', $month->format('Y-m-d') . ' 00:00')
                    ->setParameter('end', $today->format('Y-m-d') . ' 23:59');
                    break;
            }
        }
        
        return $query->getQuery()->getArrayResult();
    }

    public function getTotalTracksFavorites($user, $filter = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select("COUNT(DISTINCT f.Track)")
            ->from('Application\Entity\Favorite', 'f')
            ->leftJoin('f.Track', 't')
            ->where('f.User = :user')
            ->setParameter('user', $user);
        
        if (isset($filter['type'])) {
            $query->andWhere('t.TrackType = :type')->setParameter('type', $filter['type']);
        }
        if (isset($filter['artists'])) {
            $query->leftJoin('t.Artists', 'a')
                ->andWhere('a.id IN (:artists)')
                ->setParameter('artists', $filter['artists']);
        }
        if (isset($filter['label'])) {
            $query->andWhere('t.Label = :label')->setParameter('label', $filter['label']);
        }
        if (isset($filter['genre'])) {
            $query->andWhere('t.Genre = :genre')->setParameter('genre', $filter['genre']);
        }
        if (isset($filter['wav'])) {
            $query->andWhere("t.fileType = 'audio/x-wave'");
        }        
        if (isset($filter['start'])) {
            $query->andWhere('t.publishDate >= :start')->setParameter('start', $filter['start'] . ' 00:00');
        }
        if (isset($filter['end'])) {
            $query->andWhere('t.publishDate <= :end')->setParameter('end', $filter['end'] . ' 23:59');
        }
        if (isset($filter['last'])) {
            $today = new \DateTime();
            $yesterday = new \DateTime('yesterday');
            $week = new \DateTime('7 days ago');
            $month = new \DateTime('7 days ago');
            
            switch ($filter['last']) {
                case '0d':
                    $query->andWhere('DATE(t.publishDate) = :today')->setParameter('today', $today->format('Y-m-d'));
                    break;
                case '1d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                    ->setParameter('start', $yesterday->format('Y-m-d') . ' 00:00')
                    ->setParameter('end', $yesterday->format('Y-m-d') . ' 23:59');
                    break;
                case '7d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                    ->setParameter('start', $week->format('Y-m-d') . ' 00:00')
                    ->setParameter('end', $today->format('Y-m-d') . ' 23:59');
                    break;
                case '30d':
                    $query->andWhere('t.publishDate BETWEEN :start AND :end')
                    ->setParameter('start', $month->format('Y-m-d') . ' 00:00')
                    ->setParameter('end', $today->format('Y-m-d') . ' 23:59');
                    break;
            }
        }
        
        return $query->getQuery()->getSingleScalarResult();
    }

    public function getTracksTop($limit, $filter = [], $sortBy = [])
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('COUNT(DISTINCT d.User) as cnt', 't.id', 't.title as title', 't.publishDate as release', 'GROUP_CONCAT(a.name) as artists', 't.playtimeString as length', "REPLACE(t.wave, 'public/','/') as wave", 't.fileFormat', "REPLACE(t.sampleDestination, 'public/','/') as sample", "REPLACE(t.cover, 'public/','/') as cover", 'l.name as label', 'l.id as labelId', 'r.name as album', 'r.id as albumId', 'g.title as genre', 'g.id as genreId', 'tt.name as type')
            ->from('Application\Entity\Track', 't')
            ->leftJoin('Application\Entity\Download', 'd', 'WITH', 'd.Track = t.id')
            ->leftJoin('t.Artists', 'a')
            ->leftJoin('t.Label', 'l')
            ->leftJoin('t.Album', 'r')
            ->leftJoin('t.Genre', 'g')
            ->leftJoin('t.TrackType', 'tt')
            ->where('t.isPublished = 1')
            ->groupBy('t.id')
            ->setMaxResults($limit)
            ->orderBy('cnt', 'DESC')
            ->having('cnt > 0');
        
        if (! empty($sortBy)) {
            $query->addOrderBy($sortBy[0], $sortBy[1]);
        }
        
        if (isset($filter['type'])) {
            $query->andWhere('tt.id = :type')->setParameter('type', $filter['type']);
        }
        if (isset($filter['artists'])) {
            $query->andWhere('a.id IN (:artists)')->setParameter('artists', $filter['artists']);
        }
        if (isset($filter['label'])) {
            $query->andWhere('l.id = :label')->setParameter('label', $filter['label']);
        }
        if (isset($filter['genre'])) {
            $query->andWhere('g.id = :genre')->setParameter('genre', $filter['genre']);
        }
        if (isset($filter['album'])) {
            $query->andWhere('r.id = :album')->setParameter('album', $filter['album']);
        }
        if (isset($filter['showPromo']) && (! $filter['showPromo'] || $filter['showPromo'] == 'false')) {
            $query->andWhere('t.TrackType != 2');
        }
        
        return $query->getQuery()->getArrayResult();
    }

    public function getTrackArtists($trackId)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('a.id', 'a.name')
            ->from('Application\Entity\Track', 't')
            ->leftJoin('t.Artists', 'a')
            ->where('t.id = :track')
            ->setParameter('track', $trackId);
        
        return $query->getQuery()->getArrayResult();
    }

    public function getAlbumArtists($albumId)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('a.id', 'a.name')
            ->from('Application\Entity\Track', 't')
            ->leftJoin('t.Artists', 'a')
            ->where('t.Album = :album')
            ->setParameter('album', $albumId)
            ->groupBy('a.id');
        
        return $query->getQuery()->getArrayResult();
    }

    public function getTrackInfo($id)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('t.id', 't.title as title', 't.publishDate', "t.wave", "t.sampleDestination as sample")
            ->from('Application\Entity\Track', 't')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1);
        
        return $query->getQuery()->getOneOrNullResult();
    }

    public function getTrackForTitle($id)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('t.id', 't.title', 'GROUP_CONCAT(a.name) as artists', "REPLACE(t.cover, 'public/','/') as cover")
            ->from('Application\Entity\Track', 't')
            ->leftJoin('t.Artists', 'a')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->groupBy('t.id')
            ->setMaxResults(1);
        
        return $query->getQuery()->getOneOrNullResult();
    }

    public function getAlbumForTitle($id)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('a.name', 'l.name as label', "REPLACE(t.cover, 'public/','/') as cover")
            ->from('Application\Entity\Album', 'a')
            ->leftJoin('Application\Entity\Track', 't', 'WITH', 't.Album = a.id')
            ->leftJoin('t.Label', 'l')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1);
        
        return $query->getQuery()->getOneOrNullResult();
    }

    public function getTrack($id)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('t.id', 't.title as title', 't.publishDate as release', 'GROUP_CONCAT(a.name) as artists', 't.playtimeString as length', "REPLACE(t.wave, 'public/','/') as wave", 't.fileFormat', "REPLACE(t.sampleDestination, 'public/','/') as sample", "REPLACE(t.cover, 'public/','/') as cover", 'l.name as label', 'l.id as labelId', 'r.name as album', 'r.id as albumId', 'g.title as genre', 'g.id as genreId', 'tt.name as type')
            ->from('Application\Entity\Track', 't')
            ->leftJoin('t.Artists', 'a')
            ->leftJoin('t.Label', 'l')
            ->leftJoin('t.Album', 'r')
            ->leftJoin('t.Genre', 'g')
            ->leftJoin('t.TrackType', 'tt')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->groupBy('t.id')
            ->setMaxResults(1);
        
        return $query->getQuery()->getOneOrNullResult();
    }

    public function getAlbum($id)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('a.id', 'a.name', 'l.name as label', 'l.id as labelId', "REPLACE(t.cover, 'public/','/') as cover", 'a.date')
            ->from('Application\Entity\Album', 'a')
            ->leftJoin('Application\Entity\Track', 't', 'WITH', 't.Album = a.id')
            ->leftJoin('t.Label', 'l')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1);
        
        return $query->getQuery()->getOneOrNullResult();
    }

    public function searchArtistsArray($search, $limit = 5)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('a.id', 'a.name')
            ->from('Application\Entity\Artist', 'a')
            ->leftJoin('a.Tracks', 't')
            ->where('a.name like :search')
            ->andWhere('t.isPublished = 1')
            ->setParameter('search', '%' . $search . '%')
            ->setMaxResults($limit)
            ->groupBy('a.id')
            ->having('COUNT(t.id) > 0');
        
        return $query->getQuery()->getArrayResult();
    }

    public function searchLabelsArray($search, $limit = 5)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('a.id', 'a.name')
            ->from('Application\Entity\Label', 'a')
            ->leftJoin('Application\Entity\Track', 't', 'WITH', 't.Label = a.id')
            ->where('a.name like :search')
            ->andWhere('t.isPublished = 1')
            ->setParameter('search', '%' . $search . '%')
            ->groupBy('a.id')
            ->setMaxResults($limit);
        
        return $query->getQuery()->getArrayResult();
    }

    public function searchAlbumsArray($search, $limit = 5)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('a.id', 'a.name')
            ->from('Application\Entity\Album', 'a')
            ->leftJoin('Application\Entity\Track', 't', 'WITH', 't.Album = a.id')
            ->where('a.name like :search')
            ->andWhere('t.isPublished = 1')
            ->setParameter('search', '%' . $search . '%')
            ->groupBy('a.id')
            ->setMaxResults($limit);
        
        return $query->getQuery()->getArrayResult();
    }

    public function searchTracksArray($search, $limit = 5)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('t.id', 't.title as name')
            ->from('Application\Entity\Track', 't')
            ->where('t.title like :search')
            ->andWhere('t.isPublished = 1')
            ->setParameter('search', '%' . $search . '%')
            ->setMaxResults($limit);
        
        return $query->getQuery()->getArrayResult();
    }
}