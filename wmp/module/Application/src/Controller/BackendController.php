<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;
use Doctrine\ORM\EntityManager;
use Application\Form\LoginForm;
use Zend\Authentication\Result;
use Zend\Uri\Uri;
use \GetId3\GetId3Core as GetId3;
use Application\Form\GenreForm;
use Application\Form\SettingForm;
use Application\Entity\Genre;
use Application\Entity\BanIp;
use Application\Form\BanIpForm;
use Application\Form\PageForm;
use Application\Entity\Page;
use Application\Form\UserForm;
use Application\Entity\User;
use Application\Service\ImportManager;
use Doctrine\Common\Collections\ArrayCollection;

class BackendController extends AbstractActionController
{

    protected $session;

    private $em;

    private $config;

    private $userManager;

    private $authManager;

    private $authService;

    private $importManager;

    public function __construct($entityManager, $config, $authManager, $authService, $userManager, $importManager)
    {
        $this->em = $entityManager;
        $this->config = $config;
        $this->authManager = $authManager;
        $this->authService = $authService;
        $this->userManager = $userManager;
        $this->importManager = $importManager;
    }

    public function getEntityManager()
    {
        return $this->em;
    }

    public function checkAction()
    {
        /*
         * $title = 'Turn It Around (Instrumental Mix)';
         * $label = 2980;
         * $genre = 7;
         * $artStr = 'Kremont, Merk';
         * $ar = explode(', ', $artStr);
         * $Artists = new ArrayCollection();
         * foreach ($ar as $name){
         * $Artist = $this->em->getRepository('Application\Entity\Artist')->findOneBy([
         * 'name' => $name
         * ]);
         * $Artists[] = $Artist;
         * }
         *
         * $trackExist = $this->em->getRepository('Application\Entity\Track')->checkTrackExist($title, $label, $Artists);
         * if($trackExist){
         * echo " exist!";
         * }
         */
        $label = 'Dnc';
        $labelExist = $this->importManager->getLabel($label);
        if ($labelExist) {
            echo "exist! " . $labelExist->getId();
        } else {
            echo "not exist!";
        }
        
        exit();
    }

    public function fsmodify($obj)
    {
        $chunks = explode('/', $obj);
        chmod($obj, is_dir($obj) ? 0777 : 0644);
        if (isset($chunks[2])) {
            chown($obj, $chunks[2]);
            chgrp($obj, $chunks[2]);
        }
    }

    public function fsmodifyr($dir)
    {
        if ($objs = glob($dir . "/*")) {
            foreach ($objs as $obj) {
                $this->fsmodify($obj);
                if (is_dir($obj))
                    $this->fsmodifyr($obj);
            }
        }
        
        return $this->fsmodify($dir);
    }

    public function updateLabelAction()
    {
        $old = 165;
        $new = 4772;
        
        $updated = $this->getEntityManager()
            ->getRepository('Application\Entity\Track')
            ->updateLabel($new, $old);
        
        echo $updated;
        exit();
    }

    public function duplicateLabelAction()
    {
        $ids = [];
        echo "<pre>";
        $word = 'rec';
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('l.id', 'l.name', 'COUNT(t.id) as tracks')
            ->from('Application\Entity\Label', 'l')
            ->leftJoin('Application\Entity\Track', 't', 'WITH', 't.Label = l.id')
            ->where('l.name like :search')
            ->setParameter('search', '% ' . $word . '%')
            ->orderBy('l.name')
            ->groupBy('l.id')
            ->setMaxResults(1000);
        
        $results = $query->getQuery()->getArrayResult();
        // var_dump($results);
        
        $cnt = 0;
        foreach ($results as $entry) {
            $pos = stripos($entry['name'], ' ' . $word);
            $name = substr($entry['name'], 0, $pos);
            // echo $name.'<br/>';
            
            $query1 = $this->getEntityManager()->createQueryBuilder();
            $query1->select('l.id', 'l.name', 'COUNT(t.id) as tracks')
                ->from('Application\Entity\Label', 'l')
                ->leftJoin('Application\Entity\Track', 't', 'WITH', 't.Label = l.id')
                ->where('l.name = :search')
                ->andWhere('l.id != :id')
                ->setParameter('search', $name)
                ->setParameter('id', $entry['id'])
                ->orderBy('l.name')
                ->groupBy('l.id')
                ->setMaxResults(1);
            $result = $query1->getQuery()->getOneOrNullResult();
            
            if (count($result)) {
                echo $entry['id'] . ' ' . $entry['name'] . ' (' . $entry['tracks'] . ')<br/>';
                var_dump($result);
                /*
                 * if($entry['tracks'] > 0){
                 * $updated = $this->getEntityManager()->getRepository('Application\Entity\Track')->updateLabel($result['id'], $entry['id']);
                 * $entry['tracks'] -= $updated;
                 * }
                 * if($entry['tracks'] == 0){
                 * $label = $this->em->find('Application\Entity\Label', $entry['id']);
                 * $this->em->remove($label);
                 * $this->em->flush();
                 * }
                 */
                
                if ($cnt ++ >= 10)
                    break;
            }
        }
        exit();
        
        foreach ($results as $entry) {
            $search = $entry['name'];
            $templateValue = strtolower('records; recordings; recording; rec; music;');
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
            
            $query1 = $this->getEntityManager()->createQueryBuilder();
            $query1->select('l.id', 'l.name')
                ->from('Application\Entity\Label', 'l')
                ->where('l.name like :search1 OR l.name like :search2 OR l.name like :search3 OR l.name like :search4 OR l.name like :search5')
                ->setParameter('search1', $search . ' records')
                ->setParameter('search2', $search . ' recordings')
                ->setParameter('search3', $search . ' recording')
                ->setParameter('search4', $search . ' rec')
                ->setParameter('search5', $search . ' music')
                ->orderBy('l.name');
            $results1 = $query1->getQuery()->getArrayResult();
            
            if (count($results1) > 1) {
                var_dump($entry, $results1);
                echo "<hr/><br/>";
            }
        }
        
        // var_dump($results);
        
        exit();
    }

    public function addUserAction()
    {
        /*
         * $user = $this->userManager->addUser(['email' => 'imusic@zeit.style','password' => '@I456Mu$1k']);
         * echo $user->getId();
         */
        exit();
    }

    public function removeUnusedAction()
    {
        $query = $this->em->createQueryBuilder();
        $query->select('l')
            ->from('Application\Entity\Label', 'l')
            ->leftJoin('Application\Entity\Track', 't', 'WITH', 't.Label = l.id')
            ->where('t.id IS NULL');
        
        $labels = $query->getQuery()->getResult();
        $cnt = count($labels);
        if ($cnt) {
            foreach ($labels as $entry) {
                $this->em->remove($entry);
            }
            echo 'removed ' . $cnt . ' labels <br/>';
        }
        
        $query = $this->em->createQueryBuilder();
        $query->select('a')
            ->from('Application\Entity\Album', 'a')
            ->leftJoin('Application\Entity\Track', 't', 'WITH', 't.Album = a.id')
            ->where('t.id IS NULL');
        
        $albums = $query->getQuery()->getResult();
        $cnt = count($albums);
        if ($cnt) {
            foreach ($albums as $entry) {
                $this->em->remove($entry);
            }
            echo 'removed ' . $cnt . ' albums <br/>';
        }
        
        $query = $this->em->createQueryBuilder();
        $query->select('a')
            ->from('Application\Entity\Artist', 'a')
            ->leftJoin('a.Tracks', 't')
            ->where('t.id IS NULL');
        
        $artists = $query->getQuery()->getResult();
        $cnt = count($artists);
        if ($cnt) {
            foreach ($artists as $entry) {
                $this->em->remove($entry);
            }
            echo 'removed ' . $cnt . ' artists <br/>';
        }
        
        $this->em->flush();
        
        exit();
    }

    public function removeUnused()
    {
        $query = $this->em->createQueryBuilder();
        $query->select('l')
            ->from('Application\Entity\Label', 'l')
            ->leftJoin('Application\Entity\Track', 't', 'WITH', 't.Label = l.id')
            ->where('t.id IS NULL');
        
        $labels = $query->getQuery()->getResult();
        $cnt = count($labels);
        if ($cnt) {
            foreach ($labels as $entry) {
                $this->em->remove($entry);
            }
        }
        $query = $this->em->createQueryBuilder();
        $query->select('a')
            ->from('Application\Entity\Album', 'a')
            ->leftJoin('Application\Entity\Track', 't', 'WITH', 't.Album = a.id')
            ->where('t.id IS NULL');
        
        $albums = $query->getQuery()->getResult();
        $cnt = count($albums);
        if ($cnt) {
            foreach ($albums as $entry) {
                $this->em->remove($entry);
            }
        }
        
        $query = $this->em->createQueryBuilder();
        $query->select('a')
            ->from('Application\Entity\Artist', 'a')
            ->leftJoin('a.Tracks', 't')
            ->where('t.id IS NULL');
        
        $artists = $query->getQuery()->getResult();
        $cnt = count($artists);
        if ($cnt) {
            foreach ($artists as $entry) {
                $this->em->remove($entry);
            }
        }
        
        $this->em->flush();
    }

    public function indexAction()
    {
        return new ViewModel([]);
    }

    public function testAction()
    {
        echo "<pre>";
        $path = $this->importManager->getImportFolder() . '/2017/11-November/28-Exclusive/House/Dj Pierre - Love And Happiness 2017 (Original Mix) [Get Physical Music].wav';
        $path = $this->importManager->getImportFolder() . '/2017/11-November/28-Exclusive/Techno/Gianni Ruocco, Le Roi Carmoña - Esgarrifança (Original Mix) [Vamos Music].mp3';
        // $path = $this->importManager->getImportFolder().'/2017/11-November/28-Exclusive/Stems/Alberto Costas - Bass and Furious (Bob Ray Remix) [Elektrobeats Records].stem.mp4';
        $path = $this->importManager->getImportFolder() . '/2017/12-December/19-Promo/Electronica/Javier Alemany - Dark Pop (Original Mix) [Frequenza Black Label].wav';
        $path = $this->importManager->getImportFolder() . '/2017/12-December/19-Promo/Tech House/Antonio Rossini - Can You Dig It (Original Mix) [Flashmob Records].wav';
        // $path = $this->importManager->getImportFolder().'/2017/12-December/19-Promo/Electronica/test.wav';
        
        $path = $this->importManager->getImportFolder() . '/2017/11-November/28-Exclusive/Trance/Stranded-(Infinity-State-Remix).mp3';
        
        $info = $this->importManager->getAudioInfo($path);
        
        var_dump($info);
        exit();
    }

    public function stemAction()
    {
        $path = $this->importManager->getImportFolder() . '/2017/11-November/28-Exclusive/Stems/Alberto Costas - Bass and Furious (Bob Ray Remix) [Elektrobeats Records].stem.mp4';
        
        $info = $this->importManager->getExtension($path);
        
        var_dump($info);
        
        exit();
    }

    public function oldImportAction()
    {
        set_time_limit(0);
        $session = new Container('ImportSession');
        if (! isset($session->data)) {
            $session->data = [];
        }
        $data = [];
        $request = $this->getRequest();
        $this->layout()->contentFluid = true;
        /*
         * if ($request->isPost()) {
         * $created = 0;
         * $updated = 0;
         * $data = $session->data;
         * foreach ($data as $key => $entry){
         * if(isset($entry['warnings']['trackExistId'])){
         * $track = $entry['track'];
         * $this->importManager->updateTrack($track, $entry['warnings']['trackExistId']);
         * $data[$key]['success'] = true;
         * $updated++;
         * }elseif(empty($entry['errors'])){
         * $track = $entry['track'];
         * $this->importManager->createTrack($track);
         * $data[$key]['success'] = true;
         * $created++;
         * }
         * }
         * if($created > 0){
         * $this->flashMessenger()->addSuccessMessage('Successfully imported '.$created.' tracks!');
         * }
         * if($updated > 0){
         * $this->flashMessenger()->addSuccessMessage('Successfully updated '.$updated.' tracks!');
         * }
         * $track = $this->em->getRepository('Application\Entity\Track')->publishAll();
         *
         * }else{
         * $data = [];
         * $tracks = $this->importManager->parseImportFolder();
         * foreach ($tracks as $track){
         * $data[] = $this->importManager->validateTrack($track, $data);
         * }
         * $session->data = $data;
         * }
         */
        
        return new ViewModel([
            'title' => 'Import',
            'data' => $data
        ]);
    }

    public function importAction()
    {
        set_time_limit(0);
        $session = new Container('NewImportSession');
        $session->data = [];
        $data = [];
        $request = $this->getRequest();
        $this->layout()->contentFluid = true;
        
        $structure = $this->importManager->scanDirectories($this->importManager->getImportFolder());
        
        return new ViewModel([
            'title' => 'Import',
            'structure' => json_encode($structure)
        ]);
    }

    public function chgrpr($path, $group)
    {
        if (! is_dir($path))
            return chgrp($path, $group);
        
        $dh = opendir($path);
        while (($file = readdir($dh)) !== false) {
            if ($file != '.' && $file != '..') {
                $fullpath = $path . '/' . $file;
                if (is_link($fullpath))
                    return FALSE;
                elseif (! is_dir($fullpath) && ! chgrp($fullpath, $group))
                    return FALSE;
                elseif (! $this->chgrpr($fullpath, $group))
                    return FALSE;
            }
        }
        
        closedir($dh);
        
        if (chgrp($path, $group))
            return TRUE;
        else
            return FALSE;
    }

    public function deleteTestAction()
    {
        $path = "data/import-music/2018/01-January/04-Exclusive/House/Lvna Nox - Umbra (Original Mix) [Dnc Limited].mp3";
        $path = "data/music/Abrupt Gear - Koyrta (Original Mix) [Alter Ego Progressive].mp3";
        $path = "data/import-music/2017/12-December/27-Exclusive/Big Room/Arty, April Bender - Sunrise (Extended Mix) [Armada Music].mp3";
        // var_dump(unlink($path));
        $user_name = "www-data";
        
        /*
         * $fPath = __DIR__ . '/../../../../'.$path;
         * echo $fPath;
         */
        
        if (! file_exists($path)) {
            echo " not exist!";
        } else {
            echo " exist!<br/><pre>";
            //unlink($path);
                        
            // chown($path, $user_name);
            
             var_dump(chmod($path, 0777));
                         
             echo substr(sprintf('%o', fileperms($path)), -4).'<br>';
            
             /*$stat = stat($path);
             print_r(posix_getpwuid($stat['uid']));*/
            
        }
        
        exit();
    }

    public function searchWavAction()
    {
        set_time_limit(0);
        $tracks = $this->em->getRepository('Application\Entity\Track')->findBy([
            'fileFormat' => 'riff'
        ]);
        $cnt = 0;
        foreach ($tracks as $track) {
            if ($track->getFileDestinationMp3() == null) {
                try {
                    $mp3Path = ImportManager::convertMp3($track->getFileDestination());
                    $track->setFileDestinationMp3($mp3Path);
                    $this->em->flush($track);
                    if ($cnt ++ >= 1000)
                        break;
                } catch (\Exception $e) {
                    echo $e;
                }
            }
        }
        exit();
    }

    public function submitImportAction()
    {
        $session = new Container('NewImportSession');
        if (! isset($session->data)) {
            $session->data = [];
        }
        $result = [];
        $request = $this->getRequest();
        if ($request->isPost()) {
            $dataPost = $request->getPost();
            $index = (int) str_replace('track', '', $dataPost['index']);
            $data = [];
            
            if (isset($session->data[$index])) {
                $entry = $session->data[$index];
                $track = $entry['track'];
                if (isset($entry['warnings']['trackExistId'])) {
                    $this->importManager->updateTrack($track, $entry['warnings']['trackExistId']);
                    $result['success'] = true;
                    $result['result'] = 'updated';
                } elseif (empty($entry['errors'])) {
                    $this->importManager->createTrack($track);
                    $result['success'] = true;
                    $result['result'] = 'created';
                } elseif (! empty($entry['errors'])) {
                    unlink($track['filePath']);
                    $result['path'] = $track['filePath'];
                    $result['success'] = true;
                    $result['result'] = 'removed';
                }
            }
        }
        
        return new JsonModel($result);
    }

    public function getTrackFromStructureAction()
    {
        $session = new Container('NewImportSession');
        if (! isset($session->data)) {
            $session->data = [];
        }
        $result = [];
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost();
            $trackEntry = $this->importManager->trackFromStructure($data['path']);
            $track = $this->importManager->formatTrackFromInfo($trackEntry);
            $validatedTrack = $this->importManager->validateTrack($track, $session->data);
            $session->data[] = $validatedTrack;
            
            if (isset($validatedTrack['track']['picture'])) {
                $validatedTrack['track']['picture'] = true;
            }
            $validatedTrack['track']['publishDate'] = $validatedTrack['track']['publishDate']->format('Y-m-d');
            $validatedTrack['track']['key'] = count($session->data) - 1;
            $result = $validatedTrack;
        }
        
        return new JsonModel($result);
    }

    public function labelAction()
    {
        $name = 'Elevate Records (UK)';
        // $name = 'Manuscript Records Ukraine';
        
        $labels = $this->getEntityManager()
            ->getRepository('Application\Entity\Label')
            ->findBy([
            'name' => $name
        ]);
        $ids = [];
        $id = 55;
        $label = $this->getEntityManager()->find('Application\Entity\Label', $id);
        foreach ($labels as $entry) {
            if ($entry->getId() != $id) {
                $ids[] = $entry->getId();
            }
        }
        
        $tracks = $this->getEntityManager()
            ->getRepository('Application\Entity\Track')
            ->findBy([
            'Label' => $ids
        ]);
        foreach ($tracks as $track) {
            $track->setLabel($label);
        }
        $this->getEntityManager()->flush();
        if (count($tracks) == 0) {
            foreach ($labels as $entry) {
                $this->getEntityManager()->remove($entry);
            }
            $this->getEntityManager()->flush();
        }
        var_dump($ids);
        
        exit();
    }

    public function genreAction()
    {
        $effect = $this->params()->fromRoute('effect', 'list');
        $id = (int) $this->params()->fromRoute('id', 0);
        $create = ($effect == 'edit') ? false : true;
        
        $form = new GenreForm($this->em, $create);
        $request = $this->getRequest();
        if (! $create && $id > 0) {
            $genre = $this->em->getRepository('Application\Entity\Genre')->findOneBy([
                'id' => $id
            ]);
            if (! $genre) {
                throw new \Exception("Genre with ID=$id could not be found");
            }
        } else {
            $genre = new Genre();
        }
        
        $form->bind($genre);
        if ($request->isPost()) {
            $data = $request->getPost();
            
            if (isset($data['selected'])) {
                if (count($data['selected']) > 0) {
                    $toDelete = $this->em->getRepository('Application\Entity\Genre')->removeByIds($data['selected']);
                    $this->flashMessenger()->addSuccessMessage('Delete successfully finished');
                } else {
                    $this->flashMessenger()->addErrorMessage('Choose some genres to delete');
                }
            } else {
                $form->setData($data);
                if ($form->isValid()) {
                    if ($create)
                        $this->em->persist($genre);
                    $this->em->flush();
                    $this->flashMessenger()->addSuccessMessage('Genre successfully saved!');
                    return $this->redirect()->toRoute('backend/genre');
                } else {
                    $errors = $form->getMessages();
                    $this->flashMessenger()->addErrorMessage('Error while saving data');
                }
            }
        }
        
        $genres = $this->em->getRepository('Application\Entity\Genre')->getList();
        
        return new ViewModel([
            'title' => 'Genres',
            'genres' => $genres,
            'form' => $form,
            'effect' => $effect
        ]);
    }

    public function tracksAction()
    {
        $effect = $this->params()->fromRoute('effect', 'list');
        $id = (int) $this->params()->fromRoute('id', 0);
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost();
            if (isset($data['selected'])) {
                if (count($data['selected']) > 0) {
                    foreach ($data['selected'] as $idToDelete) {
                        $track = $this->em->find('Application\Entity\Track', $idToDelete);
                        $this->em->remove($track);
                    }
                    $this->em->flush();
                    $this->removeUnused();
                    $this->flashMessenger()->addSuccessMessage('Removed ' . count($data['selected']) . ' tracks!');
                } else {
                    $this->flashMessenger()->addErrorMessage('Choose some tracks to delete');
                }
            }
        }
        
        // $tracks = $this->em->getRepository('Application\Entity\Track')->getList();
        $this->layout()->contentFluid = true;
        $tracks = [];
        
        return new ViewModel([
            'title' => 'Tracks'
        ]);
    }

    public function trackInfoAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $track = $this->em->find('Application\Entity\Track', $id);
        
        if ($track) {
            $info = $this->importManager->getAudioInfo($track->getFileDestination());
            
            echo "<pre>";
            var_dump($info);
        }
        
        exit();
    }
    
    public function infoAction()
    {        
        $path = 'data/import-music/2018/01-January/27-Exclusive/Deep House/Hyenah feat. B’utiza, Hyenah - Usutu (Mr Raoul K Remix) [Freerange].wav';
        
        $trackEntry = $this->importManager->trackFromStructure($path);
        $track = $this->importManager->formatTrackFromInfo($trackEntry);
        $validatedTrack = $this->importManager->validateTrack($track, []);               
        
        echo "<pre>";
       // var_dump($validatedTrack);
        //echo count($validatedTrack['track']['Artists']);
        
        $Artists = $this->importManager->getArtists($validatedTrack['track']['artists_string']);
        echo count($Artists['Artists']);
        
        exit;
    }

    public function downloadsAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        $this->layout()->contentFluid = true;
        $track = $this->em->find('Application\Entity\Track', $id);
        
        return new ViewModel([
            'title' => 'Downloads',
            'id' => $id,
            'track' => $track
        ]);
    }

    public function getDownloadsAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $columns = [
            'user',
            'ip',
            'date'
        ];
        $request = $this->getRequest();
        $result = [
            'data' => []
        ];
        
        if ($request->isPost()) {
            $data = $request->getPost();
            
            $sortOrder = (isset($data['order'])) ? $data['order'][0]['dir'] : 'asc';
            $sortBy = (isset($data['order'])) ? $columns[$data['order'][0]['column']] : '';
            $search = (isset($data['search'])) ? $data['search']['value'] : '';
            $total = $this->em->getRepository('Application\Entity\Track')->getDownloadsTotal($id, $search);
            
            $start = $data['start'];
            $limit = $data['length'];
            
            $result = [
                'draw' => $data['draw'],
                'recordsFiltered' => $total,
                'recordsTotal' => $total
            ];
            $tracks = $this->em->getRepository('Application\Entity\Track')->getDownloadsList($id, $start, $limit, $sortBy, $sortOrder, $search);
            foreach ($tracks as $key => $track) {
                $tracks[$key]['date'] = $track['date']->format('Y-m-d');
            }
            
            $result['data'] = $tracks;
        }
        
        return new JsonModel($result);
    }

    public function getTracksAction()
    {
        $columns = [
            'id',
            'title',
            'fileType',
            'artists',
            'label',
            'genre',
            'album',
            'publishDate',
            'created',
            'cover',
            'wave',
            'type',
            'isPublished',
            'downloaded'
        ];
        $request = $this->getRequest();
        $result = [
            'data' => []
        ];
        
        if ($request->isPost()) {
            $data = $request->getPost();
            
            $sortOrder = (isset($data['order'])) ? $data['order'][0]['dir'] : 'asc';
            $sortBy = (isset($data['order'])) ? $columns[$data['order'][0]['column']] : '';
            $search = (isset($data['search'])) ? $data['search']['value'] : '';
            $total = $this->em->getRepository('Application\Entity\Track')->getTotal($search);
            
            $start = $data['start'];
            $limit = $data['length'];
            
            $result = [
                'draw' => $data['draw'],
                'recordsFiltered' => $total,
                'recordsTotal' => $total
            ];
            $tracks = $this->em->getRepository('Application\Entity\Track')->getList($start, $limit, $sortBy, $sortOrder, $search);
            foreach ($tracks as $key => $track) {
                $tracks[$key]['publishDate'] = $track['publishDate']->format('Y-m-d');
                $tracks[$key]['created'] = $track['created']->format('Y-m-d H:i:s');
                $tracks[$key]['title'] = $track['title'] . '<br/>(' . $track['playtimeString'] . ')';
                // $tracks[$key]['downloaded'] = ($track['downloaded'] > 0 && $track['artCnt'] > 1)?$track['downloaded']/$track['artCnt']:$track['downloaded'];
            }
            
            $result['data'] = $tracks;
        }
        
        return new JsonModel($result);
    }

    public function start($page, $limit)
    {
        return ($page - 1) * $limit;
    }

    public function updateWaveAction()
    {
        $result = [];
        $id = (int) $this->params()->fromRoute('id');
        $track = $this->em->getRepository('Application\Entity\Track')->getTrackInfo($id);
        
        if ($track) {
            $track['year'] = $track['publishDate']->format('Y');
            $track['month_number'] = $track['publishDate']->format('m');
            $track['day'] = $track['publishDate']->format('d');
            $waveform = $this->importManager->createWave($track, $track['sample'], true);
            if (! is_array($waveform)) {
                $trackEntry = $this->em->find('Application\Entity\Track', $id);
                $trackEntry->setWave($waveform);
                $this->em->flush();
                $result['wave'] = str_replace('public/', '/', $waveform);
            } else {
                $result = $waveform;
            }
        }
        
        return new JsonModel($result);
        exit();
    }

    public function publishTracksAction()
    {
        $track = $this->em->getRepository('Application\Entity\Track')->publishAll();
        return $this->redirect()->toRoute('backend/tracks');
    }

    public function publishTracksJsonAction()
    {
        $track = $this->em->getRepository('Application\Entity\Track')->publishAll();
        return new JsonModel([
            'success' => true
        ]);
    }

    public function banAction()
    {
        $effect = $this->params()->fromRoute('effect', 'list');
        $id = (int) $this->params()->fromRoute('id', 0);
        $create = ($effect == 'edit') ? false : true;
        
        $form = new BanIpForm($this->em, $create);
        $request = $this->getRequest();
        if (! $create && $id > 0) {
            $ban = $this->em->getRepository('Application\Entity\BanIp')->findOneBy([
                'id' => $id
            ]);
            if (! $ban) {
                throw new \Exception("BanIp with ID=$id could not be found");
            }
        } else {
            $ban = new BanIp();
        }
        
        $form->bind($ban);
        if ($request->isPost()) {
            $data = $request->getPost();
            
            if (isset($data['selected'])) {
                if (count($data['selected']) > 0) {
                    $toDelete = $this->em->getRepository('Application\Entity\BanIp')->removeByIds($data['selected']);
                    $this->flashMessenger()->addSuccessMessage('Delete successfully finished');
                } else {
                    $this->flashMessenger()->addErrorMessage('Choose some ips to delete');
                }
            } else {
                $form->setData($data);
                if ($form->isValid()) {
                    if ($create)
                        $this->em->persist($ban);
                    $this->em->flush();
                    $this->flashMessenger()->addSuccessMessage('Ban successfully saved!');
                    return $this->redirect()->toRoute('backend/ban_ip');
                } else {
                    $errors = $form->getMessages();
                    $this->flashMessenger()->addErrorMessage('Error while saving data');
                }
            }
        }
        
        $bans = $this->em->getRepository('Application\Entity\BanIp')->getList();
        
        return new ViewModel([
            'title' => 'Ban IP',
            'bans' => $bans,
            'form' => $form,
            'effect' => $effect
        ]);
    }

    public function pageAction()
    {
        $effect = $this->params()->fromRoute('effect', 'list');
        $id = (int) $this->params()->fromRoute('id', 0);
        $create = ($effect == 'edit') ? false : true;
        
        $form = new PageForm($this->em, $create);
        $request = $this->getRequest();
        if (! $create && $id > 0) {
            $page = $this->em->getRepository('Application\Entity\Page')->findOneBy([
                'id' => $id
            ]);
            if (! $page) {
                throw new \Exception("Page with ID=$id could not be found");
            }
        } else {
            $page = new Page();
        }
        
        $form->bind($page);
        if ($request->isPost()) {
            $data = $request->getPost();
            
            if (isset($data['selected'])) {
                if (count($data['selected']) > 0) {
                    $toDelete = $this->em->getRepository('Application\Entity\Page')->removeByIds($data['selected']);
                    $this->flashMessenger()->addSuccessMessage('Delete successfully finished');
                } else {
                    $this->flashMessenger()->addErrorMessage('Choose some pages to delete');
                }
            } else {
                $form->setData($data);
                if ($form->isValid()) {
                    if ($create)
                        $this->em->persist($page);
                    $this->em->flush();
                    $this->flashMessenger()->addSuccessMessage('Page successfully saved!');
                    return $this->redirect()->toRoute('backend/page');
                } else {
                    $errors = $form->getMessages();
                    $this->flashMessenger()->addErrorMessage('Error while saving data');
                }
            }
        }
        
        $pages = $this->em->getRepository('Application\Entity\Page')->getListNotPayment();
        
        return new ViewModel([
            'title' => 'Pages',
            'pages' => $pages,
            'form' => $form,
            'effect' => $effect
        ]);
    }

    public function paymentPageAction()
    {
        $effect = $this->params()->fromRoute('effect', 'list');
        $id = (int) $this->params()->fromRoute('id', 0);
        $create = ($effect == 'edit') ? false : true;
        
        $form = new PageForm($this->em, $create);
        $request = $this->getRequest();
        if (! $create && $id > 0) {
            $page = $this->em->getRepository('Application\Entity\Page')->findOneBy([
                'id' => $id
            ]);
            if (! $page) {
                throw new \Exception("Page with ID=$id could not be found");
            }
        } else {
            $page = new Page();
        }
        
        $form->bind($page);
        if ($request->isPost()) {
            $data = $request->getPost();
            
            if (isset($data['selected'])) {
                if (count($data['selected']) > 0) {
                    $toDelete = $this->em->getRepository('Application\Entity\Page')->removeByIds($data['selected']);
                    $this->flashMessenger()->addSuccessMessage('Delete successfully finished');
                } else {
                    $this->flashMessenger()->addErrorMessage('Choose some pages to delete');
                }
            } else {
                $form->setData($data);
                if ($form->isValid()) {
                    if ($create) {
                        $page->setIsPayment(1);
                        $this->em->persist($page);
                    }
                    $this->em->flush();
                    $this->flashMessenger()->addSuccessMessage('Page successfully saved!');
                    return $this->redirect()->toRoute('backend/payment_page');
                } else {
                    $errors = $form->getMessages();
                    $this->flashMessenger()->addErrorMessage('Error while saving data');
                }
            }
        }
        
        $pages = $this->em->getRepository('Application\Entity\Page')->getListPayment();
        
        return new ViewModel([
            'title' => 'Pages',
            'pages' => $pages,
            'form' => $form,
            'effect' => $effect
        ]);
    }

    public function userAction()
    {
        $effect = $this->params()->fromRoute('effect', 'list');
        $id = (int) $this->params()->fromRoute('id', 0);
        $create = ($effect == 'edit') ? false : true;
        
        $form = new UserForm($this->em, $create);
        $request = $this->getRequest();
        if (! $create && $id > 0) {
            $user = $this->em->getRepository('Application\Entity\User')->findOneBy([
                'id' => $id
            ]);
            if (! $user) {
                throw new \Exception("User with ID=$id could not be found");
            }
        } else {
            $user = new User();
        }
        
        $form->bind($user);
        if ($request->isPost()) {
            $data = $request->getPost();
            
            if (isset($data['selected'])) {
                if (count($data['selected']) > 0) {
                    $toDelete = $this->em->getRepository('Application\Entity\User')->removeByIds($data['selected']);
                    $this->flashMessenger()->addSuccessMessage('Delete successfully finished');
                } else {
                    $this->flashMessenger()->addErrorMessage('Choose some pages to delete');
                }
            } else {
                $form->setData($data);
                if ($form->isValid()) {
                    if ($create)
                        $this->em->persist($user);
                    $this->em->flush();
                    $this->flashMessenger()->addSuccessMessage('User successfully saved!');
                    return $this->redirect()->toRoute('backend/user');
                } else {
                    $errors = $form->getMessages();
                    $this->flashMessenger()->addErrorMessage('Error while saving data');
                }
            }
        }
        
        $users = $this->em->getRepository('Application\Entity\User')->getList();
        
        return new ViewModel([
            'title' => 'Users',
            'users' => $users,
            'form' => $form,
            'effect' => $effect
        ]);
    }

    public function settingAction()
    {
        $code = $this->params()->fromRoute('code');
        
        $form = new SettingForm($this->em, $code);
        $request = $this->getRequest();
        $setting = $this->em->getRepository('Application\Entity\Setting')->findOneBy([
            'code' => $code
        ]);
        if (! $setting) {
            throw new \Exception("Setting with code=$code could not be found");
        }
        
        $form->bind($setting);
        if ($request->isPost()) {
            $data = $request->getPost();
            
            $form->setData($data);
            if ($form->isValid()) {
                $this->em->flush();
                $this->flashMessenger()->addSuccessMessage('Setting successfully saved!');
            } else {
                $errors = $form->getMessages();
                $this->flashMessenger()->addErrorMessage('Error while saving data');
            }
        }
        $view = new ViewModel([
            'title' => 'Setting',
            'form' => $form
        ]);
        
        if ($code == 'maintain_mode') {
            $view->setTemplate('application/backend/setting-maintain');
        } elseif ($code == 'site_mode') {
            $view->setTemplate('application/backend/setting-site');
        } elseif ($code == 'footer') {
            $view->setTemplate('application/backend/setting-footer');
        }
        
        return $view;
    }

    public function loginAction()
    {
        $redirectUrl = (string) $this->params()->fromQuery('redirectUrl', '');
        if (strlen($redirectUrl) > 2048) {
            throw new \Exception("Too long redirectUrl argument passed");
        }
        
        if ($this->authService->hasIdentity()) {
            if (empty($redirectUrl)) {
                return $this->redirect()->toRoute('backend');
            } else {
                $this->redirect()->toUrl($redirectUrl);
            }
        }
        // Check if we do not have users in database at all. If so, create
        // the 'Admin' user.
        $this->userManager->createAdminUserIfNotExists();
        
        $sessionRedirect = new Container('redirect');
        $queryParams = $this->params()->fromQuery();
        if (isset($queryParams['redirectTo'])) {
            $sessionRedirect->redirect = $queryParams['redirectTo'];
        }
        
        $this->layout('layout/backend-login');
        $form = new LoginForm($this->em);
        $form->get('redirect_url')->setValue($redirectUrl);
        $messages = null;
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $result = $this->authManager->login($data['email'], $data['password'], $data['remember_me']);
                
                // Check result.
                if ($result->getCode() == Result::SUCCESS) {
                    
                    // Get redirect URL.
                    $redirectUrl = $this->params()->fromPost('redirect_url', '');
                    
                    if (! empty($redirectUrl)) {
                        // The below check is to prevent possible redirect attack
                        // (if someone tries to redirect user to another domain).
                        $uri = new Uri($redirectUrl);
                        if (! $uri->isValid() || $uri->getHost() != null)
                            throw new \Exception('Incorrect redirect URL: ' . $redirectUrl);
                    }
                    // If redirect URL is provided, redirect the user to that URL;
                    // otherwise redirect to Home page.
                    if (empty($redirectUrl)) {
                        return $this->redirect()->toRoute('backend');
                    } else {
                        $this->redirect()->toUrl($redirectUrl);
                    }
                }
            }
        }
        
        return new ViewModel([
            'form' => $form,
            'redirectUrl' => $redirectUrl
        ]);
    }

    public function logoutAction()
    {
        $this->authManager->logout();
        return $this->redirect()->toRoute('backend/login');
    }
}
