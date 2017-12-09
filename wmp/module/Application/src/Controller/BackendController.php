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

    
    public function addUserAction()
    {
        /* $user = $this->userManager->addUser(['email' => 'imusic@zeit.style','password' => '@I456Mu$1k']);
        echo $user->getId(); */
        exit;
    }
    
    public function indexAction()
    {       
       
        return new ViewModel([
        ]);
    }    
    
    public function importAction()
    {
        set_time_limit(0);
        $session = new Container('ImportSession');
        if(!isset($session->data)){
            $session->data = [];
        }
        $request = $this->getRequest();
        $this->layout()->contentFluid = true;
        
        if ($request->isPost()) {
            $created = 0;
            $updated = 0;
            $data = $session->data;
            foreach ($data as $key => $entry){
                if(isset($entry['warnings']['trackExistId'])){                    
                    $track = $entry['track'];             
                    $this->importManager->updateTrack($track, $entry['warnings']['trackExistId']);
                    $data[$key]['success'] = true;
                    $updated++;
                }elseif(empty($entry['errors'])){
                    $track = $entry['track'];             
                    $this->importManager->createTrack($track);
                    $data[$key]['success'] = true;
                    $created++;
                }
            }    
            if($created > 0){
                $this->flashMessenger()->addSuccessMessage('Successfully imported '.$created.' tracks!');
            }
            if($updated > 0){
                $this->flashMessenger()->addSuccessMessage('Successfully updated '.$updated.' tracks!');
            }
            $track = $this->em->getRepository('Application\Entity\Track')->publishAll();
            
        }else{
            $data = [];
            $tracks = $this->importManager->parseImportFolder();
            foreach ($tracks as $track){
                $data[] = $this->importManager->validateTrack($track, $data);
            }
            $session->data = $data;
        }
                
        return new ViewModel([
            'title'     =>  'Import',
            'data' =>  $data
        ]);
    }
    
    public function labelAction()
    {
        $name = 'Elevate Records (UK)';
        //$name = 'Manuscript Records Ukraine';
        
        $labels = $this->getEntityManager()->getRepository('Application\Entity\Label')->findBy(['name' => $name]);
        $ids = [];
        $id = 55;
        $label = $this->getEntityManager()->find('Application\Entity\Label',$id);
        foreach ($labels as $entry){
            if($entry->getId() != $id){
                $ids[] = $entry->getId();
            }
        }
        
        $tracks = $this->getEntityManager()->getRepository('Application\Entity\Track')->findBy(['Label' => $ids]); 
        foreach ($tracks as $track){
            $track->setLabel($label);
        }
        $this->getEntityManager()->flush();
        if(count($tracks) == 0){
            foreach ($labels as $entry){
                $this->getEntityManager()->remove($entry);
            }
            $this->getEntityManager()->flush();
        }
        var_dump($ids);
        
        exit;        
    }
    
    public function genreAction()
    {
        $effect = $this->params()->fromRoute('effect', 'list');
        $id = (int) $this->params()->fromRoute('id', 0);
        $create = ($effect == 'edit')?false:true;
        
        $form = new GenreForm($this->em, $create);
        $request = $this->getRequest();
        if(!$create && $id > 0){
            $genre = $this->em->getRepository('Application\Entity\Genre')->findOneBy(['id' => $id]);
            if(!$genre){
               throw new \Exception("Genre with ID=$id could not be found");                
            }
        }else{
            $genre = new Genre();
        }
        
        $form->bind($genre);
        if ($request->isPost()) {
            $data = $request->getPost();
            
            if(isset($data['selected'])){
                if(count($data['selected']) > 0){
                    $toDelete = $this->em->getRepository('Application\Entity\Genre')->removeByIds($data['selected']);
                    $this->flashMessenger()->addSuccessMessage('Delete successfully finished');
                }else{
                    $this->flashMessenger()->addErrorMessage('Choose some genres to delete');
                }
            }else{
                $form->setData($data);
                if ($form->isValid()) {
                    if($create)
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
            'title'     =>  'Genres',
            'genres' => $genres,
            'form'  =>  $form,
            'effect'    =>  $effect
        ]);
    }
    
    public function tracksAction()
    {
        $effect = $this->params()->fromRoute('effect', 'list');
        $id = (int) $this->params()->fromRoute('id', 0);
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost();
            if(isset($data['selected'])){
                if(count($data['selected']) > 0){
                    foreach ($data['selected'] as $idToDelete){
                        $track = $this->em->find('Application\Entity\Track',$idToDelete);
                        $this->em->remove($track);
                    }
                    $this->em->flush();
                    $this->flashMessenger()->addSuccessMessage('Removed '.count($data['selected']).' tracks!');
                }else{
                    $this->flashMessenger()->addErrorMessage('Choose some tracks to delete');
                }
            }
        }
        
        //$tracks = $this->em->getRepository('Application\Entity\Track')->getList();
        $this->layout()->contentFluid = true;
        $tracks = [];
        
        return new ViewModel([
            'title'     =>  'Tracks',
        ]);
    }
    
    public function getTracksAction()
    {
        $columns = ['id','title','fileType','artists','label','genre','album','publishDate','cover','wave','type'];
        $request = $this->getRequest();
        $result = ['data' => []];
        
        if ($request->isPost()) {
            $data = $request->getPost();
            
            $sortOrder = (isset($data['order']))?$data['order'][0]['dir']:'asc';
            $sortBy = (isset($data['order']))?$columns[$data['order'][0]['column']]:'';
            $search = (isset($data['search']))?$data['search']['value']:'';            
            $total = $this->em->getRepository('Application\Entity\Track')->getTotal($search);
            
            $start = $data['start'];
            $limit = $data['length'];
            
            $result = [
                'draw' => $data['draw'],
                'recordsFiltered' =>  $total,
                'recordsTotal' =>  $total,
            ];
            $tracks = $this->em->getRepository('Application\Entity\Track')->getList($start, $limit, $sortBy, $sortOrder, $search);
            foreach ($tracks as $key => $track)
            {
                $tracks[$key]['publishDate'] = $track['publishDate']->format('Y-m-d');
                $tracks[$key]['title'] = $track['title'].'<br/>('.$track['playtimeString'].')';
            }
            
            $result['data'] = $tracks;
        }
        
        return new JsonModel($result);
    }
    
    public function start($page, $limit)
    {
        return ($page - 1)*$limit;
    }    
    
    public function updateWaveAction()
    {
        $result = [];
        $id = (int)$this->params()->fromRoute('id');
        $track = $this->em->getRepository('Application\Entity\Track')->getTrackInfo($id);
        
        if($track){
            $track['year'] = $track['publishDate']->format('Y');  
            $track['month_number'] = $track['publishDate']->format('m'); 
            $track['day'] = $track['publishDate']->format('d');
            $waveform = $this->importManager->createWave($track, $track['sample'], true);
            if(!is_array($waveform)){
                $trackEntry = $this->em->find('Application\Entity\Track',$id);
                $trackEntry->setWave($waveform);
                $this->em->flush();
                $result['wave'] = str_replace('public/','/',$waveform);
            }else{
                $result = $waveform;
            }      
        }        
        
        return new JsonModel($result);
        exit;
    }    
    
    public function publishTracksAction()
    {
        $track = $this->em->getRepository('Application\Entity\Track')->publishAll();        
        return $this->redirect()->toRoute('backend/tracks');
    }

    public function banAction()
    {
        $effect = $this->params()->fromRoute('effect', 'list');
        $id = (int) $this->params()->fromRoute('id', 0);
        $create = ($effect == 'edit')?false:true;
    
        $form = new BanIpForm($this->em, $create);
        $request = $this->getRequest();
        if(!$create && $id > 0){
            $ban = $this->em->getRepository('Application\Entity\BanIp')->findOneBy(['id' => $id]);
            if(!$ban){
                throw new \Exception("BanIp with ID=$id could not be found");
            }
        }else{
            $ban = new BanIp();
        }
    
        $form->bind($ban);
        if ($request->isPost()) {
            $data = $request->getPost();
    
            if(isset($data['selected'])){
                if(count($data['selected']) > 0){
                    $toDelete = $this->em->getRepository('Application\Entity\BanIp')->removeByIds($data['selected']);
                    $this->flashMessenger()->addSuccessMessage('Delete successfully finished');
                }else{
                    $this->flashMessenger()->addErrorMessage('Choose some ips to delete');
                }
            }else{
                $form->setData($data);
                if ($form->isValid()) {
                    if($create)
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
            'title'     =>  'Ban IP',
            'bans' => $bans,
            'form'  =>  $form,
            'effect'    =>  $effect
        ]);
    }
    
    public function pageAction()
    {
        $effect = $this->params()->fromRoute('effect', 'list');
        $id = (int) $this->params()->fromRoute('id', 0);
        $create = ($effect == 'edit')?false:true;
        
        $form = new PageForm($this->em, $create);
        $request = $this->getRequest();
        if(!$create && $id > 0){
            $page = $this->em->getRepository('Application\Entity\Page')->findOneBy(['id' => $id]);
            if(!$page){
               throw new \Exception("Page with ID=$id could not be found");                
            }
        }else{
            $page = new Page();
        }
        
        $form->bind($page);
        if ($request->isPost()) {
            $data = $request->getPost();
            
            if(isset($data['selected'])){
                if(count($data['selected']) > 0){
                    $toDelete = $this->em->getRepository('Application\Entity\Page')->removeByIds($data['selected']);
                    $this->flashMessenger()->addSuccessMessage('Delete successfully finished');
                }else{
                    $this->flashMessenger()->addErrorMessage('Choose some pages to delete');
                }
            }else{
                $form->setData($data);
                if ($form->isValid()) {
                    if($create)
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
            'title'     =>  'Pages',
            'pages' => $pages,
            'form'  =>  $form,
            'effect'    =>  $effect
        ]);
    }
    
    public function paymentPageAction()
    {
        $effect = $this->params()->fromRoute('effect', 'list');
        $id = (int) $this->params()->fromRoute('id', 0);
        $create = ($effect == 'edit')?false:true;
    
        $form = new PageForm($this->em, $create);
        $request = $this->getRequest();
        if(!$create && $id > 0){
            $page = $this->em->getRepository('Application\Entity\Page')->findOneBy(['id' => $id]);
            if(!$page){
                throw new \Exception("Page with ID=$id could not be found");
            }
        }else{
            $page = new Page();
        }
    
        $form->bind($page);
        if ($request->isPost()) {
            $data = $request->getPost();
    
            if(isset($data['selected'])){
                if(count($data['selected']) > 0){
                    $toDelete = $this->em->getRepository('Application\Entity\Page')->removeByIds($data['selected']);
                    $this->flashMessenger()->addSuccessMessage('Delete successfully finished');
                }else{
                    $this->flashMessenger()->addErrorMessage('Choose some pages to delete');
                }
            }else{
                $form->setData($data);
                if ($form->isValid()) {
                    if($create){
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
            'title'     =>  'Pages',
            'pages' => $pages,
            'form'  =>  $form,
            'effect'    =>  $effect
        ]);
    }
    
    public function userAction()
    {
        $effect = $this->params()->fromRoute('effect', 'list');
        $id = (int) $this->params()->fromRoute('id', 0);
        $create = ($effect == 'edit')?false:true;
        
        $form = new UserForm($this->em, $create);
        $request = $this->getRequest();
        if(!$create && $id > 0){
            $user = $this->em->getRepository('Application\Entity\User')->findOneBy(['id' => $id]);
            if(!$user){
               throw new \Exception("User with ID=$id could not be found");                
            }
        }else{
            $user = new User();
        }
        
        $form->bind($user);
        if ($request->isPost()) {
            $data = $request->getPost();
            
            if(isset($data['selected'])){
                if(count($data['selected']) > 0){
                    $toDelete = $this->em->getRepository('Application\Entity\User')->removeByIds($data['selected']);
                    $this->flashMessenger()->addSuccessMessage('Delete successfully finished');
                }else{
                    $this->flashMessenger()->addErrorMessage('Choose some pages to delete');
                }
            }else{
                $form->setData($data);
                if ($form->isValid()) {
                    if($create)
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
            'title'     =>  'Users',
            'users' => $users,
            'form'  =>  $form,
            'effect'    =>  $effect
        ]);
    }

    public function settingAction()
    {
        $code = $this->params()->fromRoute('code');
    
        $form = new SettingForm($this->em,$code);
        $request = $this->getRequest();
        $setting = $this->em->getRepository('Application\Entity\Setting')->findOneBy(['code' => $code]);
        if(!$setting){
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
            'title' =>  'Setting',
            'form'  =>  $form
        ]);
        
        if($code == 'maintain_mode'){
            $view->setTemplate('application/backend/setting-maintain');
        }elseif($code == 'site_mode'){
            $view->setTemplate('application/backend/setting-site');
        }elseif($code == 'footer'){
            $view->setTemplate('application/backend/setting-footer');
        }   
    
        return $view;
    }
    
    public function loginAction()
    {
        $redirectUrl = (string)$this->params()->fromQuery('redirectUrl', '');
        if (strlen($redirectUrl)>2048) {
            throw new \Exception("Too long redirectUrl argument passed");
        }
        
        if($this->authService->hasIdentity()){
            if(empty($redirectUrl)) {
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
        if(isset($queryParams['redirectTo'])){
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
                    
                    if (!empty($redirectUrl)) {
                        // The below check is to prevent possible redirect attack 
                        // (if someone tries to redirect user to another domain).
                        $uri = new Uri($redirectUrl);
                        if (!$uri->isValid() || $uri->getHost()!=null)
                            throw new \Exception('Incorrect redirect URL: ' . $redirectUrl);
                    }
                    // If redirect URL is provided, redirect the user to that URL;
                    // otherwise redirect to Home page.
                    if(empty($redirectUrl)) {
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
