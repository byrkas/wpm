<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Doctrine\ORM\EntityManager;
use Zend\Authentication\Result;
use Zend\Uri\Uri;
use Firebase\JWT\JWT;
use Application\Entity\Download;
use Application\Entity\Favorite;
use Zend\Mail\Message;
use Zend\Http\Headers;
use Zend\Http\Response\Stream;
use Application\Service\ImportManager;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Mime;

class IndexController extends AbstractActionController
{

    protected $session;

    private $em;

    private $config;

    private $userManager;

    private $authManager;

    private $authService;

    protected $static = 'http://api.wpm.zeit.style';
    protected $staticImg = 'http://static.wpm.zeit.style';

    protected $sortList = [
        'release',
        'title',
        'label',
        'genre',
        'created'
    ];

    protected $jwtAuth = [
        'cypherKey' => 'wpmR1a#2%dY2fX@3g8r5&s4Kf6*sd(5dHs!5gD4s',
        'tokenAlgorithm' => 'HS256'
    ];

    /**
     *
     * @var type string
     */
    public $token;

    /**
     *
     * @var type Object or Array
     */
    public $tokenPayload;

    public function __construct($entityManager, $config, $authManager, $authService, $userManager)
    {
        $this->em = $entityManager;
        $this->config = $config;
        $this->authManager = $authManager;
        $this->authService = $authService;
        $this->userManager = $userManager;
        $this->static = $config['static'];
    }

    public function getEntityManager()
    {
        return $this->em;
    }

    public function robotsAction()
    {
        $uri = $this->params()->fromQuery('route');
        $args = $this->params()->fromQuery('args');

        $data = [
            'title' => 'Who Play Music',
            'description' => 'Download and listen to new, exclusive, electronic dance music and house tracks. Available on mp3 and wav at the world’s largest store for DJs.'
        ];

        if (strpos($uri, '/track/') !== FALSE) {
            $trackId = intval(str_replace('/track/', '', $uri));
            if ($trackId) {
                $track = $this->em->getRepository('Application\Entity\Track')->getTrackForTitle($trackId);
                if ($track) {
                    $data['title'] = $track['title'] . ' by ' . $track['artists'] . ' |' . $data['title'];
                    if (! $track['cover'])
                        $track['cover'] = '/img/music.png';
                    $track['cover'] = $this->static . $track['cover'];
                    $data['image'] = $track['cover'];
                }
            }
        } elseif (strpos($uri, '/album/') !== FALSE) {
            $id = intval(str_replace('/album/', '', $uri));
            if ($id) {
                $album = $this->em->getRepository('Application\Entity\Track')->getAlbumForTitle($id);
                if ($album) {
                    $data['title'] = $album['name'] . ' by ' . $album['label'] . ' |' . $data['title'];
                    if (! $album['cover'])
                        $album['cover'] = '/img/music.png';
                    $album['cover'] = $this->static . $album['cover'];
                    $data['image'] = $album['cover'];
                }
            }
        } else {
            switch ($uri) {
                case '/tracks':
                    $data['title'] = 'Tracks |' . $data['title'];
                    if ($args) {
                        $argsVar = [];
                        parse_str($args, $argsVar);
                        if (isset($argsVar['artists'])) {}
                    }
                    break;
                case '/top100':
                    $data['title'] = 'TOP 100 Tracks |' . $data['title'];
                    break;
                case '/page/about-us':
                    $data['title'] = 'About us |' . $data['title'];
                    break;
                case '/page/contact-us':
                    $data['title'] = 'Contact us |' . $data['title'];
                    break;
                case '/page/faq-help':
                    $data['title'] = 'FAQ |' . $data['title'];
                    break;
                case '/page/report-abuse':
                    $data['title'] = 'Report Abuse |' . $data['title'];
                    break;
            }
        }
        $view = new ViewModel($data);
        $view->setTerminal(true);

        return $view;
    }

    public function maintainAction()
    {
        $this->layout('layout/maintain');
        return new ViewModel([]);
    }

    public function getTransport()
    {
        $transport = new SmtpTransport();
        $options   = new SmtpOptions([
            'name' => 'yandex',
            'host' => 'smtp.yandex.com',
            'port' => 465,
            'connection_class'  => 'login',
            'connection_config' => [
                'username' => 'people@whoplaymusic.com',
                'password' => 'Cristen9-',
                'ssl'      => 'ssl',
            ],
        ]);
        $transport->setOptions($options);

        return $transport;
    }

    public function smtpAction()
    {
        $mail = new Message();
        $mail->setEncoding('UTF-8');
        $mail->addFrom('people@whoplaymusic.com', 'Who play music');
        $mail->addTo('svetlana.byrka@gmail.com');
        $mail->setSubject('New sign up from site WPM');
        $mail->setBody('Full name: ');

        $this->getTransport()->send($mail);

        exit;
    }

    public function mailAction()
    {
        $mail = new Message();
        $mail->setEncoding('UTF-8');
        $mail->addFrom('wpm@wpm.zeit.style', 'Who play music');
        $mail->addTo('svetlana.byrka@gmail.com');
        $mail->setSubject('New sign up from site WPM');
        $mail->setBody('Full name: ');

        $this->getTransport()->send($mail);

        /*
         * $to = 'svetlana.byrka@gmail.com';
         * $subject = 'the subject';
         * $message = 'hello';
         * $headers = 'From: webmaster@example.com' . "\r\n" .
         * 'Reply-To: webmaster@example.com' . "\r\n" .
         * 'X-Mailer: PHP/' . phpversion();
         *
         * mail($to, $subject, $message, $headers);
         */

        exit();
    }

    public function start($page, $limit)
    {
        return ($page - 1) * $limit;
    }

    public function isMaintainAction()
    {
        $request = $this->getRequest();
        $result['logout'] = 0;
        $result['quotes'] = null;
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
            $user = $this->em->find('Application\Entity\User', $userId);
            if(!$user->getActive()){
                $result['logout'] = 1;
            }
            $result['quotes'] = [
                'quoteExclusive' => $user->quoteExclusive,
                'quotePromo' => $user->quotePromo,
                'expireDate' => $user->expireDate->format('Y-m-d'),
                'showPromo' => $user->showPromo
            ];
        }

        $result['isMaintain'] = (int) $this->getEntityManager()
            ->getRepository('Application\Entity\Setting')
            ->checkMaintainMode();
        if (! $result['isMaintain']) {
            $request = $this->getRequest();
            $remoteAddr = $request->getServer('REMOTE_ADDR');
            if ($remoteAddr) {
                $result['isMaintain'] = (int) $this->getEntityManager()
                    ->getRepository('Application\Entity\BanIp')
                    ->isBanned($remoteAddr);
            }
        }
        $result['siteMode'] = (int) $this->getEntityManager()
            ->getRepository('Application\Entity\Setting')
            ->getSiteMode();
        $result['footer'] = $this->getEntityManager()
            ->getRepository('Application\Entity\Setting')
            ->getFooter();

        return new JsonModel($result);
    }

    public function ipAction()
    {
        $result = [];
        $request = $this->getRequest();
        $remoteAddr = $request->getServer('REMOTE_ADDR');
        $result['ip'] = $remoteAddr;

        return new JsonModel($result);
    }

    public function tracksAction()
    {
        $result = [];
        $filter = [];
        $userId = null;
        $downloadedIds = [];
        $request = $this->getRequest();
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
        }
        $sort = $this->params()->fromQuery('sort', 'release-desc');
        $limit = (int) $this->params()->fromQuery('limit', 100);
        $page = (int) $this->params()->fromQuery('page', 1);
        $filter['showPromo'] = $this->params()->fromQuery('showPromo', true);
        $sortArr = explode('-', $sort);
        if (! in_array($sortArr[0], $this->sortList)) {
            $sortArr = [
                'release',
                'desc'
            ];
        }
        if (! in_array($sortArr[1], [
            'asc',
            'desc'
        ])) {
            $sortArr[1] = 'desc';
        }
        if ($this->params()->fromQuery('artists')) {
            $filter['artists'] = explode(',', $this->params()->fromQuery('artists'));
        }
        if ($this->params()->fromQuery('genre')) {
            $filter['genre'] = (int) $this->params()->fromQuery('genre');
        }
        if ($this->params()->fromQuery('label')) {
            $filter['label'] = (int) $this->params()->fromQuery('label');
        }
        if ($this->params()->fromQuery('last')) {
            $filter['last'] = $this->params()->fromQuery('last');
        }
        if ($this->params()->fromQuery('start')) {
            $filter['start'] = $this->params()->fromQuery('start');
        }
        if ($this->params()->fromQuery('end')) {
            $filter['end'] = $this->params()->fromQuery('end');
        }
        if ($this->params()->fromQuery('type')) {
            $filter['type'] = (int) $this->params()->fromQuery('type');
        }
        if ($this->params()->fromQuery('wav')) {
            $filter['wav'] = (int) $this->params()->fromQuery('wav');
        }
        $total = $this->em->getRepository('Application\Entity\Track')->getTotalTracks($filter);

        $start = $this->start($page, $limit);
        while ($start > $total) {
            $start = $this->start(-- $page, $limit);
        }

        $tracks = $this->em->getRepository('Application\Entity\Track')->getTracks($limit, $start, $filter, $sortArr);
        $result['total'] = (int) $total;
        $result['page'] = $page;
        $result['limit'] = $limit;
        $artists = $this->em->getRepository('Application\Entity\Track')->getArtists($filter);
        if (! empty($artists)) {
            foreach ($artists as $key => $artist) {
                $artists[$key]['checked'] = (isset($filter['artists']) && in_array($artist['id'], $filter['artists'])) ? 1 : 0;
            }
        }
        $result['artists'] = $artists;
        $result['types'] = $this->em->getRepository('Application\Entity\Track')->getTypes($filter);
        $result['labels'] = $this->em->getRepository('Application\Entity\Track')->getLabels($filter);
        $result['genres'] = $this->em->getRepository('Application\Entity\Track')->getGenres($filter);

        if (! empty($tracks)) {
            if($userId){
                $trackIds = [];
                foreach ($tracks as $track){
                    $trackIds[] = $track['id'];
                }
                $downloadedIds = $this->em->getRepository('Application\Entity\User')->getDownloadedIds($userId, $trackIds);
            }
            foreach ($tracks as $key => $track) {
                $tracks[$key]['artists'] = $this->em->getRepository('Application\Entity\Track')->getTrackArtists($track['id']);
                $tracks[$key]['url'] = $this->static . str_replace('public/', '/', $track['sample']);
                unset($tracks[$key]['sample']);
                if (! $track['cover']){
                    $tracks[$key]['cover'] = $this->staticImg.'/music.png';
                }                
                else{
                    $tracks[$key]['cover'] = $this->staticImg.'/400x400'. str_replace('public/media/img/', '/', $track['cover']);
                }
                $tracks[$key]['downloaded'] = (in_array($track['id'], $downloadedIds));
                
                //$tracks[$key]['wave'] = $this->static . $track['wave'];
                $tracks[$key]['release'] = $track['release']->format('Y-m-d');
            }
            $result['tracks'] = $tracks;
        }
        return new JsonModel($result);
    }

    public function trackAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        $result = [];
        $userId = null;
        $downloadedIds = [];
        $request = $this->getRequest();
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
        }
        $track = $this->em->getRepository('Application\Entity\Track')->getTrack($id);
        if ($track) {
            if($userId){
                $downloadedIds = $this->em->getRepository('Application\Entity\User')->getDownloadedIds($userId, [$track['id']]);
            }
            $track['artists'] = $this->em->getRepository('Application\Entity\Track')->getTrackArtists($track['id']);
            $track['url'] = $this->static . str_replace('public/', '/', $track['sample']);
            unset($track['sample']);
            if (! $track['cover']){
                $track['cover'] = $this->staticImg.'/music.png';
            }
            else{
                $track['cover'] = $this->staticImg.'/400x400'. str_replace('public/media/img/', '/', $track['cover']);
            }
            $track['downloaded'] = (in_array($track['id'], $downloadedIds));
            $track['release'] = $track['release']->format('Y-m-d');
            $filter = [
                'genre' => $track['genreId']
            ];
            $filter['showPromo'] = $this->params()->fromQuery('showPromo', true);
            $recommends = $this->em->getRepository('Application\Entity\Track')->getTracksTop(20, $filter);
            if(!empty($recommends)){
                if($userId){
                    $trackIds = [];
                    foreach ($recommends as $rec){
                        $trackIds[] = $rec['id'];
                    }
                    $downloadedIds = $this->em->getRepository('Application\Entity\User')->getDownloadedIds($userId, $trackIds);
                }
            }
            foreach ($recommends as $key => $rec) {
                $recommends[$key]['artists'] = $this->em->getRepository('Application\Entity\Track')->getTrackArtists($rec['id']);
                $recommends[$key]['url'] = $this->static . str_replace('public/', '/', $rec['sample']);
                unset($recommends[$key]['sample']);
                if (! $rec['cover']){
                    $recommends[$key]['cover'] = $this->staticImg.'/music.png';
                }
                else{
                    $recommends[$key]['cover'] = $this->staticImg.'/400x400'. str_replace('public/media/img/', '/', $rec['cover']);
                }  
                $recommends[$key]['release'] = $rec['release']->format('Y-m-d');
                $recommends[$key]['downloaded'] = (in_array($rec['id'], $downloadedIds));
            }
            $track['recommends'] = $recommends;
        }
        $result['track'] = $track;
        return new JsonModel($result);
    }

    public function pageAction()
    {
        $slug = $this->params()->fromRoute('slug');
        $result = [];
        $page = $this->em->getRepository('Application\Entity\Page')->getPageBySlug($slug);
        if ($page) {
            $result['page'] = $page;
        }

        return new JsonModel($result);
    }

    public function albumAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $result = [];
        $userId = null;
        $downloadedIds = [];
        $request = $this->getRequest();
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
        }
        $album = $this->em->getRepository('Application\Entity\Track')->getAlbum($id);
        if (! $album['cover'])
            $album['cover'] = '/img/music.png';
        $album['cover'] = $this->static . $album['cover'];
        $album['release'] = $album['date']->format('Y-m-d');
        $album['artists'] = $this->em->getRepository('Application\Entity\Track')->getAlbumArtists($id);

        $result['album'] = $album;
        $tracks = $this->em->getRepository('Application\Entity\Track')->getTracks(100, 0, [
            'album' => $id
        ]);
        if (! empty($tracks)) {
            if($userId){
                $trackIds = [];
                foreach ($tracks as $track){
                    $trackIds[] = $track['id'];
                }
                $downloadedIds = $this->em->getRepository('Application\Entity\User')->getDownloadedIds($userId, $trackIds);
            }
            foreach ($tracks as $key => $track) {
                $tracks[$key]['artists'] = $this->em->getRepository('Application\Entity\Track')->getTrackArtists($track['id']);
                $tracks[$key]['url'] = $this->static . str_replace('public/', '/', $track['sample']);
                unset($tracks[$key]['sample']);
                if (! $track['cover']){
                    $tracks[$key]['cover'] = $this->staticImg.'/music.png';
                }
                else{
                    $tracks[$key]['cover'] = $this->staticImg.'/400x400'. str_replace('public/media/img/', '/', $track['cover']);
                }
                $tracks[$key]['downloaded'] = (in_array($track['id'], $downloadedIds));
                $tracks[$key]['release'] = $track['release']->format('Y-m-d');
            }
            $result['tracks'] = $tracks;
        }

        return new JsonModel($result);
    }

    public function indexAction()
    {
        exit();
        $tracks = $this->em->getRepository('Application\Entity\Track')->getTracks();
        $types = $this->em->getRepository('Application\Entity\Track')->getTypes();
        $genres = $this->em->getRepository('Application\Entity\Track')->getGenres();
        $artists = $this->em->getRepository('Application\Entity\Track')->getArtists();
        $labels = $this->em->getRepository('Application\Entity\Track')->getLabels();

        return new ViewModel([
            'title' => 'Tracks',
            'tracks' => $tracks,
            'types' => $types,
            'genres' => $genres,
            'artists' => $artists,
            'labels' => $labels
        ]);
    }

    public function loginAction()
    {
        $result = [];
        $success = false;
        $messages = [];
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $this->fromJson();
            if (! empty($data['username']) && ! empty($data['password'])) {
                $user = $this->em->getRepository('Application\Entity\User')->findOneBy([
                    'email' => $data['username'],
                    'password' => $data['password']
                ]);
                if ($user && $user->getActive()) {
                    $success = true;
                    $payload = [
                        'email' => $user->email,
                        'username' => $user->email,
                        'id' => $user->id
                    ];
                    $result['token'] = $this->generateJwtToken($payload);
                    $result['quotes'] = [
                        'quoteExclusive' => $user->quoteExclusive,
                        'quotePromo' => $user->quotePromo,
                        'expireDate' => $user->expireDate->format('Y-m-d'),
                        'showPromo' => $user->showPromo
                    ];
                } else {
                    $messages[] = 'Username or password is incorrect!';
                }
            } else {
                $messages[] = 'Enter login and password!';
            }
        }
        $result['success'] = $success;
        $result['message'] = implode(' ', $messages);

        return new JsonModel($result);
    }

    public function signupAction()
    {
        $pubKey = '6LdC0jIUAAAAABX9nREg28dzpXF902M8DfqtXtoP';
        $privKey = '6LdC0jIUAAAAAI-Tq0q4SLShBBQsF8F8o08SqhnI';

        $result = [];
        $success = false;
        $messages = [];
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $this->fromJson();
            $recaptcha = new \ReCaptcha\ReCaptcha($privKey);

            if (empty($data['captcha'])) {
                $messages[] = 'Captcha is required!';
            } else {
                /*
                 * $resp = $recaptcha->verify($data['captcha']);
                 * if ($resp->isSuccess()){
                 */
                if (empty($data['fullname'])) {
                    $messages[] = 'Fullname is required!';
                }
                if (empty($data['email'])) {
                    $messages[] = 'Email is required!';
                } else {
                    $user = $this->em->getRepository('Application\Entity\User')->findOneBy([
                        'email' => $data['email']
                    ]);
                    if ($user) {
                        $messages[] = 'User with such email already exist!';
                    }
                }
                if (empty($data['payment'])) {
                    $messages[] = 'Payment option is required!';
                }
                if (empty($data['info'])) {
                    $messages[] = 'Addition info is required!';
                }
                if (empty($messages)) {
                    $mail = new Message();
                    $mail->setEncoding('UTF-8');
                    $mail->addFrom('people@whoplaymusic.com', 'Who play music');
                    $mail->addTo('people@whoplaymusic.com');
                    $mail->setSubject('New sign up from site WPM');

                    $html = new MimePart('Full name: ' . $data['fullname'] . '<br/>' . 'Email: ' . $data['email'] . '<br/>' . 'Payment: ' . $data['payment'] . '<br/>' . 'Additional info: ' . $data['info'] . '<br/>');
                    $html->type = Mime::TYPE_HTML;
                    $html->charset = 'utf-8';
                    $html->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
                    $body = new MimeMessage();
                    $body->addPart($html);
                    $mail->setBody($body);

                    $this->getTransport()->send($mail);
                    $success = true;
                    $messages[] = 'Thank you for signup! You will receive email with account data!';
                }
                /*
                 * }else{
                 * $messages[] = 'Captcha is wrong!';
                 * $messages[] = implode(',', $resp->getErrorCodes());
                 * }
                 */
            }
        }
        $result['success'] = $success;
        $result['message'] = implode(' ', $messages);

        return new JsonModel($result);
    }

    // TODO: mail template
    public function forgotAction()
    {
        $result = [];
        $success = false;
        $messages = [];
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $this->fromJson();

            if (empty($data['email'])) {
                $messages[] = 'Email is required!';
            }
            if (empty($messages)) {
                $userExist = $this->getEntityManager()
                    ->getRepository('Application\Entity\User')
                    ->findOneBy([
                    'email' => $data['email']
                ]);
                if ($userExist) {
                    $mail = new Message();
                    $mail->setEncoding('UTF-8');
                    $mail->addFrom('people@whoplaymusic.com', 'Who play music');
                    $mail->addTo($data['email']);
                    $mail->setSubject('WhoPlayMusic account');

                    $html = new MimePart('Your password: ' . $userExist->getPassword() . '<br/>');
                    $html->type = Mime::TYPE_HTML;
                    $html->charset = 'utf-8';
                    $html->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
                    $body = new MimeMessage();
                    $body->addPart($html);
                    $mail->setBody($body);

                    $this->getTransport()->send($mail);
                    $success = true;
                    $messages[] = 'You will receive email with account data!';
                } else {
                    $messages[] = 'User not exist!';
                }
            }
        }
        $result['success'] = $success;
        $result['message'] = implode(' ', $messages);

        return new JsonModel($result);
    }

    public function profileAction()
    {
        $result = [];
        $success = false;
        $userId = null;
        $request = $this->getRequest();
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
        }
        if (! $userId)
            return new JsonModel($result);
        $messages = [];
        if ($request->isPost()) {
            $data = $this->fromJson();

            if (empty($data['old_password'])) {
                $messages[] = 'Old password is required!';
            }
            if (empty($data['new_password'])) {
                $messages[] = 'New password is required!';
            }
            if (empty($messages)) {
                $userExist = $this->getEntityManager()
                    ->getRepository('Application\Entity\User')
                    ->findOneBy([
                    'id' => $userId
                ]);
                if ($userExist) {
                    $password = $userExist->getPassword();
                    if ($password != $data['old_password']) {
                        $messages[] = 'Old password is wrong! Please try again.';
                    }
                    if (empty($messages)) {
                        $success = true;
                        $userExist->setPassword($data['new_password']);
                        $this->em->flush();
                        $messages[] = 'Your password was successfully changed!';
                    }
                }
            }
        }
        $result['success'] = $success;
        $result['message'] = implode(' ', $messages);

        return new JsonModel($result);
    }

    public function paymentPageAction()
    {
        $result = [];
        $request = $this->getRequest();
        $this->checkAuthorization($request);
        $userId = null;
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
        }
        $page = $this->em->getRepository('Application\Entity\Page')->getPaymentByUser($userId);

        if ($page) {
            $result['page'] = $page;
        }

        return new JsonModel($result);
    }

    public function downloadAction()
    {
        $now = new \DateTime(date('Y-m-d') . ' 23:59:59');
        $filter = new \Zend\Filter\Word\SeparatorToDash();
        $result = [];
        $success = false;
        $messages = [];
        $quote = [];
        $quoteSub = false;
        $id = (int) $this->params()->fromRoute('id', 0);
        $request = $this->getRequest();
        $userId = null;
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
            if ($userId) {
                $track = $this->em->find('Application\Entity\Track', $id);
                $user = $this->em->find('Application\Entity\User', $userId);
                if ($track && $user) {
                    $quotePromo = $user->getQuotePromo();
                    $quoteExclusive = $user->getQuoteExclusive();
                    $downloaded = $this->em->getRepository('Application\Entity\Download')->findOneBy([
                        'User' => $user,
                        'Track' => $track
                    ]);
                    if ($downloaded) {
                        $success = true;
                    } else {
                        $quoteType = 'quote' . $track->getTrackType()->getName();
                        $quote = $user->__get($quoteType);
                        $expireDate = $user->getExpireDate();
                        if ($quote > 0 && $expireDate >= $now) {
                            $success = true;
                            $quoteSub = true;
                            if ($quoteType == 'quotePromo')
                                $quotePromo --;
                            else
                                $quoteExclusive --;
                        } else {
                            $messages[] = 'Quote was expired';
                        }
                    }
                    $quote = [
                        'quotePromo' => $quotePromo,
                        'quoteExclusive' => $quoteExclusive
                    ];
                } else {
                    $messages[] = 'Track or user wrong!';
                }
            }
        } else {
            $messages[] = 'User is not authorized!';
        }
        $result['success'] = $success;
        $result['messages'] = implode(' ', $messages);
        $result['quoteSub'] = $quoteSub;
        $result['quote'] = $quote;

        return new JsonModel($result);
    }

    public function downloadAlbumAction()
    {
        $now = new \DateTime(date('Y-m-d') . ' 23:59:59');
        $id = (int) $this->params()->fromRoute('id', 0);
        $result = [];
        $success = false;
        $messages = [];
        $quote = [];
        $quoteSub = false;
        $request = $this->getRequest();
        $userId = null;
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
            if ($userId) {
                $user = $this->em->find('Application\Entity\User', $userId);
                $expireDate = $user->getExpireDate();
                $quotePromo = $user->getQuotePromo();
                $quoteExclusive = $user->getQuoteExclusive();

                if ($expireDate >= $now) {
                    $tracks = $this->em->getRepository('Application\Entity\Track')->findBy([
                        'Album' => $id
                    ]);
                    foreach ($tracks as $track) {
                        $downloaded = $this->em->getRepository('Application\Entity\Download')->findOneBy([
                            'User' => $user,
                            'Track' => $track
                        ]);
                        if ($downloaded) {
                            $success = true;
                        } else {
                            $quoteType = 'quote' . $track->getTrackType()->getName();

                            if ($quoteType == 'quotePromo' && $quotePromo > 0) {
                                $success = true;
                                $quoteSub = true;
                                $quotePromo --;
                            } elseif ($quoteType == 'quoteExclusive' && $quoteExclusive > 0) {
                                $success = true;
                                $quoteSub = true;
                                $quoteExclusive --;
                            } else {
                                $messages[] = 'Quote was expired';
                                $success = false;
                                break;
                            }
                        }
                    }
                    $quote = [
                        'quotePromo' => $quotePromo,
                        'quoteExclusive' => $quoteExclusive
                    ];
                } else {
                    $messages[] = 'Quote was expired ' . $expireDate->format('Y-m-d H:i') . ' ' . $now->format('Y-m-d H:i');
                }
            }
        } else {
            $messages[] = 'User is not authorized!';
        }
        $result['success'] = $success;
        $result['messages'] = implode(' ', $messages);
        $result['quoteSub'] = $quoteSub;
        $result['quote'] = $quote;

        return new JsonModel($result);
    }

    public function downloadTopAction()
    {
        $now = new \DateTime(date('Y-m-d') . ' 23:59:59');
        $result = [];
        $success = false;
        $messages = [];
        $quote = [];
        $quoteSub = false;
        $request = $this->getRequest();
        $userId = null;
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
            if ($userId) {
                $user = $this->em->find('Application\Entity\User', $userId);
                $expireDate = $user->getExpireDate();
                $quotePromo = $user->getQuotePromo();
                $quoteExclusive = $user->getQuoteExclusive();

                if ($expireDate >= $now) {
                    $filter = [
                        'trackIds' => []
                    ];
                    $filter['showPromo'] = $this->params()->fromQuery('showPromo', true);
                    $limit = (int) $this->params()->fromQuery('limit', 100);

                    if ($this->params()->fromQuery('artists')) {
                        $filter['artists'] = explode(',', $this->params()->fromQuery('artists'));
                    }
                    if ($this->params()->fromQuery('genre')) {
                        $filter['genre'] = (int) $this->params()->fromQuery('genre');
                    }
                    if ($this->params()->fromQuery('label')) {
                        $filter['label'] = (int) $this->params()->fromQuery('label');
                    }
                    if ($this->params()->fromQuery('type')) {
                        $filter['type'] = (int) $this->params()->fromQuery('type');
                    }

                    $tracks = $this->em->getRepository('Application\Entity\Track')->getTracksTop($limit, $filter);
                    $trackIds = [];
                    foreach ($tracks as $trEntry) {
                        $trackIds[] = $trEntry['id'];
                    }
                    $filter['trackIds'] = $trackIds;
                    $tracks = $this->em->getRepository('Application\Entity\Track')->findBy([
                        'id' => $filter['trackIds']
                    ]);
                    $downloadedAlready = 0;
                    foreach ($tracks as $track) {
                        $downloaded = $this->em->getRepository('Application\Entity\Download')->findOneBy([
                            'User' => $user,
                            'Track' => $track
                        ]);
                        if ($downloaded) {
                            $success = true;
                            $downloadedAlready ++;
                        } else {
                            $quoteType = 'quote' . $track->getTrackType()->getName();

                            if ($quoteType == 'quotePromo' && $quotePromo > 0) {
                                $success = true;
                                $quoteSub = true;
                                $quotePromo --;
                            } elseif ($quoteType == 'quoteExclusive' && $quoteExclusive > 0) {
                                $success = true;
                                $quoteSub = true;
                                $quoteExclusive --;
                            } else {
                                $messages[] = 'Quote was expired';
                                $success = false;
                                break;
                            }
                        }
                    }
                    $messages[] = "Already downloaded $downloadedAlready tracks";
                    $quote = [
                        'quotePromo' => $quotePromo,
                        'quoteExclusive' => $quoteExclusive
                    ];
                } else {
                    $messages[] = 'Quote was expired ' . $expireDate->format('Y-m-d H:i') . ' ' . $now->format('Y-m-d H:i');
                }
            }
        } else {
            $messages[] = 'User is not authorized!';
        }
        $result['success'] = $success;
        $result['messages'] = implode(' ', $messages);
        $result['quoteSub'] = $quoteSub;
        $result['quote'] = $quote;

        return new JsonModel($result);
    }

    public function downloadTopStreamAction()
    {
        $nameFilter = new \Zend\Filter\Word\SeparatorToDash();
        $request = $this->getRequest();
        $remoteAddr = $request->getServer('REMOTE_ADDR');
        $userId = null;
        $maxSize = 4;
        $maxSizeLimited = false;

        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;

            if ($userId) {
                $User = $this->em->getReference('Application\Entity\User', $userId);
                $filter = [
                    'trackIds' => []
                ];
                $filter['showPromo'] = $this->params()->fromQuery('showPromo', true);
                $limit = (int) $this->params()->fromQuery('limit', 100);

                if ($this->params()->fromQuery('artists')) {
                    $filter['artists'] = explode(',', $this->params()->fromQuery('artists'));
                }
                if ($this->params()->fromQuery('genre')) {
                    $filter['genre'] = (int) $this->params()->fromQuery('genre');
                }
                if ($this->params()->fromQuery('label')) {
                    $filter['label'] = (int) $this->params()->fromQuery('label');
                }
                if ($this->params()->fromQuery('type')) {
                    $filter['type'] = (int) $this->params()->fromQuery('type');
                }

                $tracks = $this->em->getRepository('Application\Entity\Track')->getTracksTop($limit, $filter);
                $trackIds = [];
                foreach ($tracks as $trEntry) {
                    $trackIds[] = $trEntry['id'];
                }
                $filter['trackIds'] = $trackIds;

                $tracks = $this->em->getRepository('Application\Entity\Track')->getTracksForArchive($limit, 0, $filter);

                $zipName = date('d_m_Y') . '_top_' . count($tracks) . '.zip';
                $contentArr = [];
                $bytes = 0;

                foreach ($tracks as $track) {
                    $size = $track['fileSize'];
                    $bytes += $size;
                    if ($this->formatSizeGb($bytes) > $maxSize) {
                        $maxSizeLimited = true;
                        break;
                    }
                    $extension = pathinfo($track['fileDestination'], PATHINFO_EXTENSION);
                    if($extension == 'mp4'){
                        $extension = 'stem.mp4';
                    }
                    $fileName = $track['artists'] . ' - ' . $track['title'] . (($track['label']) ? ' [' . $track['label'] . ']' : '') . '.' . $extension;
                    $filePath = str_replace('public/', '/', $track['fileDestination']);
                    $crc32 = ($track['crc32']) ? $track['crc32'] : hash_file('crc32b', realpath($track['fileDestination']));
                    $contentArr[] = "$crc32 $size $filePath $fileName";

                    $Track = $this->em->getReference('Application\Entity\Track', $track['id']);

                    $downloaded = $this->em->getRepository('Application\Entity\Download')->findOneBy([
                        'User' => $User,
                        'Track' => $track
                    ]);
                    if (! $downloaded) {
                        $User->subQuote($track['type']);
                    }
                    $download = new Download($Track, $User);
                    $download->setIp($remoteAddr);
                    $this->em->persist($download);
                    $this->em->flush();
                }
                $content = implode("\r\n", $contentArr) . "\r\n";

                $response = $this->getResponse();
                $headers = $response->getHeaders();
                $headers->addHeaderLine("Content-Disposition: attachment; filename=\"" . $zipName . "\"");
                $headers->addHeaderLine("X-filename: " . $zipName);
                $headers->addHeaderLine("X-Archive-Files: zip");
                $headers->addHeaderLine("Cache-control: private");
                $headers->addHeaderLine("Content-encoding: none");
                $headers->addHeaderLine("Accept-Encoding: ''");
                if ($maxSizeLimited) {
                    $headers->addHeaderLine('X-CustomHeader: Archive has reached maximum allowed limit 4Gb, please filter results!');
                } else {
                    $headers->addHeaderLine('X-CustomHeader: ');
                }
                $response->setContent($content);
                return $this->getResponse();
            }
        }

        exit();
    }

    public function downloadTracksAction()
    {
        $now = new \DateTime(date('Y-m-d') . ' 23:59:59');
        $result = [];
        $success = false;
        $messages = [];
        $quote = [];
        $quoteSub = false;
        $request = $this->getRequest();
        $userId = null;
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
            if ($userId) {
                $user = $this->em->find('Application\Entity\User', $userId);
                $expireDate = $user->getExpireDate();
                $quotePromo = $user->getQuotePromo();
                $quoteExclusive = $user->getQuoteExclusive();

                if ($expireDate >= $now) {
                    $filter = [];
                    $sort = $this->params()->fromQuery('sort', 'release-desc');
                    $limit = (int) $this->params()->fromQuery('limit', 100);
                    $page = (int) $this->params()->fromQuery('page', 1);
                    $filter['showPromo'] = $this->params()->fromQuery('showPromo', true);
                    $sortArr = explode('-', $sort);
                    if (! in_array($sortArr[0], $this->sortList)) {
                        $sortArr = [
                            'release',
                            'desc'
                        ];
                    }
                    if (! in_array($sortArr[1], [
                        'asc',
                        'desc'
                    ])) {
                        $sortArr[1] = 'desc';
                    }
                    if ($this->params()->fromQuery('artists')) {
                        $filter['artists'] = explode(',', $this->params()->fromQuery('artists'));
                    }
                    if ($this->params()->fromQuery('genre')) {
                        $filter['genre'] = (int) $this->params()->fromQuery('genre');
                    }
                    if ($this->params()->fromQuery('label')) {
                        $filter['label'] = (int) $this->params()->fromQuery('label');
                    }
                    if ($this->params()->fromQuery('last')) {
                        $filter['last'] = $this->params()->fromQuery('last');
                    }
                    if ($this->params()->fromQuery('start')) {
                        $filter['start'] = $this->params()->fromQuery('start');
                    }
                    if ($this->params()->fromQuery('end')) {
                        $filter['end'] = $this->params()->fromQuery('end');
                    }
                    if ($this->params()->fromQuery('type')) {
                        $filter['type'] = (int) $this->params()->fromQuery('type');
                    }
                    if ($this->params()->fromQuery('wav')) {
                        $filter['wav'] = (int) $this->params()->fromQuery('wav');
                    }
                    $total = $this->em->getRepository('Application\Entity\Track')->getTotalTracks($filter);

                    $start = $this->start($page, $limit);
                    while ($start > $total) {
                        $start = $this->start(-- $page, $limit);
                    }

                    $tracksIds = $this->em->getRepository('Application\Entity\Track')->getTrackIds($limit, $start, $filter, $sortArr);

                    $tracks = $this->em->getRepository('Application\Entity\Track')->findBy([
                        'id' => $tracksIds
                    ]);
                    $downloadedAlready = 0;
                    foreach ($tracks as $track) {
                        $downloaded = $this->em->getRepository('Application\Entity\Download')->findOneBy([
                            'User' => $user,
                            'Track' => $track
                        ]);
                        if ($downloaded) {
                            $success = true;
                            $downloadedAlready++;
                        } else {
                            $quoteType = 'quote' . $track->getTrackType()->getName();

                            if ($quoteType == 'quotePromo' && $quotePromo > 0) {
                                $success = true;
                                $quoteSub = true;
                                $quotePromo --;
                            } elseif ($quoteType == 'quoteExclusive' && $quoteExclusive > 0) {
                                $success = true;
                                $quoteSub = true;
                                $quoteExclusive --;
                            } else {
                                $messages[] = 'Quote was expired';
                                $success = false;
                                break;
                            }
                        }
                    }
                    $quote = [
                        'quotePromo' => $quotePromo,
                        'quoteExclusive' => $quoteExclusive
                    ];
                    $messages[] = "Downloaded already $downloadedAlready tracks";
                } else {
                    $messages[] = 'Quote was expired ' . $expireDate->format('Y-m-d H:i') . ' ' . $now->format('Y-m-d H:i');
                }
            }
        } else {
            $messages[] = 'User is not authorized!';
        }
        $result['success'] = $success;
        $result['messages'] = implode(' ', $messages);
        $result['quoteSub'] = $quoteSub;
        $result['quote'] = $quote;

        return new JsonModel($result);
    }

    public function downloadTracksStreamAction()
    {
        $nameFilter = new \Zend\Filter\Word\SeparatorToDash();
        $request = $this->getRequest();
        $remoteAddr = $request->getServer('REMOTE_ADDR');
        $userId = null;
        $maxSize = 4;
        $maxSizeLimited = false;

        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;

            if ($userId) {
                $User = $this->em->getReference('Application\Entity\User', $userId);
                $filter = [];
                $sort = $this->params()->fromQuery('sort', 'release-desc');
                $limit = (int) $this->params()->fromQuery('limit', 100);
                $page = (int) $this->params()->fromQuery('page', 1);
                $filter['showPromo'] = $this->params()->fromQuery('showPromo', true);
                $sortArr = explode('-', $sort);
                if (! in_array($sortArr[0], $this->sortList)) {
                    $sortArr = [
                        'release',
                        'desc'
                    ];
                }
                if (! in_array($sortArr[1], [
                    'asc',
                    'desc'
                ])) {
                    $sortArr[1] = 'desc';
                }
                if ($this->params()->fromQuery('artists')) {
                    $filter['artists'] = explode(',', $this->params()->fromQuery('artists'));
                }
                if ($this->params()->fromQuery('genre')) {
                    $filter['genre'] = (int) $this->params()->fromQuery('genre');
                }
                if ($this->params()->fromQuery('label')) {
                    $filter['label'] = (int) $this->params()->fromQuery('label');
                }
                if ($this->params()->fromQuery('last')) {
                    $filter['last'] = $this->params()->fromQuery('last');
                }
                if ($this->params()->fromQuery('start')) {
                    $filter['start'] = $this->params()->fromQuery('start');
                }
                if ($this->params()->fromQuery('end')) {
                    $filter['end'] = $this->params()->fromQuery('end');
                }
                if ($this->params()->fromQuery('type')) {
                    $filter['type'] = (int) $this->params()->fromQuery('type');
                }
                if ($this->params()->fromQuery('wav')) {
                    $filter['wav'] = (int) $this->params()->fromQuery('wav');
                }
                $total = $this->em->getRepository('Application\Entity\Track')->getTotalTracks($filter);

                $start = $this->start($page, $limit);
                while ($start > $total) {
                    $start = $this->start(-- $page, $limit);
                }

                $tracksIds = $this->em->getRepository('Application\Entity\Track')->getTrackIds($limit, $start, $filter, $sortArr);
                $filter['trackIds'] = $tracksIds;

                $tracks = $this->em->getRepository('Application\Entity\Track')->getTracksForArchive($limit, 0, $filter);

                $zipName = date('d_m_Y') . '_archive_' . count($tracks) . '.zip';
                $contentArr = [];
                $bytes = 0;

                foreach ($tracks as $track) {
                    $size = $track['fileSize'];
                    $bytes += $size;
                    if ($this->formatSizeGb($bytes) > $maxSize) {
                        $maxSizeLimited = true;
                        break;
                    }
                    $extension = pathinfo($track['fileDestination'], PATHINFO_EXTENSION);
                   /*  if($extension == 'mp4'){
                        $extension = 'stem.mp4';
                    } */
                    $fileName = $track['artists'] . ' - ' . $track['title'] . (($track['label']) ? ' [' . $track['label'] . ']' : '') . '.' . $extension;
                    $filePath = str_replace('public/', '/', $track['fileDestination']);
                    $crc32 = ($track['crc32']) ? $track['crc32'] : hash_file('crc32b', realpath($track['fileDestination']));
                    $contentArr[] = "$crc32 $size $filePath $fileName";

                    $Track = $this->em->getReference('Application\Entity\Track', $track['id']);

                    $downloaded = $this->em->getRepository('Application\Entity\Download')->findOneBy([
                        'User' => $User,
                        'Track' => $track
                    ]);
                    if (! $downloaded) {
                        $User->subQuote($track['type']);
                    }
                    $download = new Download($Track, $User);
                    $download->setIp($remoteAddr);
                    $this->em->persist($download);
                    $this->em->flush();
                }
                $content = implode("\r\n", $contentArr) . "\r\n";

                $response = $this->getResponse();
                $headers = $response->getHeaders();
                $headers->addHeaderLine("Content-Disposition: attachment; filename=\"" . $zipName . "\"");
                $headers->addHeaderLine("X-filename: " . $zipName);
                $headers->addHeaderLine("X-Archive-Files: zip");
                $headers->addHeaderLine("Cache-control: private");
                $headers->addHeaderLine("Content-encoding: none");
                $headers->addHeaderLine("Accept-Encoding: ''");
                if ($maxSizeLimited) {
                    $headers->addHeaderLine('X-CustomHeader: Archive has reached maximum allowed limit 4Gb, please filter results!');
                } else {
                    $headers->addHeaderLine('X-CustomHeader: ');
                }
                $response->setContent($content);
                return $this->getResponse();
            }
        }

        exit();
    }

    public function downloadFavoritesAction()
    {
        $now = new \DateTime(date('Y-m-d') . ' 23:59:59');
        $result = [];
        $success = false;
        $messages = [];
        $quote = [];
        $quoteSub = false;
        $request = $this->getRequest();
        $userId = null;
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
            if ($userId) {
                $user = $this->em->find('Application\Entity\User', $userId);
                $expireDate = $user->getExpireDate();
                $quotePromo = $user->getQuotePromo();
                $quoteExclusive = $user->getQuoteExclusive();

                if ($expireDate >= $now) {
                    $filter = [
                        'user' => $userId,
                        'favorites' => 1
                    ];
                    $sort = $this->params()->fromQuery('sort', 'created-desc');
                    $limit = (int) $this->params()->fromQuery('limit', 100);
                    $page = (int) $this->params()->fromQuery('page', 1);
                    $sortArr = explode('-', $sort);
                    if (! in_array($sortArr[0], $this->sortList)) {
                        $sortArr = [
                            'release',
                            'desc'
                        ];
                    }
                    if (! in_array($sortArr[1], [
                        'asc',
                        'desc'
                    ])) {
                        $sortArr[1] = 'desc';
                    }
                    if ($this->params()->fromQuery('artists')) {
                        $filter['artists'] = explode(',', $this->params()->fromQuery('artists'));
                    }
                    if ($this->params()->fromQuery('genre')) {
                        $filter['genre'] = (int) $this->params()->fromQuery('genre');
                    }
                    if ($this->params()->fromQuery('label')) {
                        $filter['label'] = (int) $this->params()->fromQuery('label');
                    }
                    if ($this->params()->fromQuery('type')) {
                        $filter['type'] = (int) $this->params()->fromQuery('type');
                    }
                    if ($this->params()->fromQuery('wav')) {
                        $filter['wav'] = (int) $this->params()->fromQuery('wav');
                    }
                    if ($this->params()->fromQuery('last')) {
                        $filter['last'] = $this->params()->fromQuery('last');
                    }
                    if ($this->params()->fromQuery('start')) {
                        $filter['start'] = $this->params()->fromQuery('start');
                    }
                    if ($this->params()->fromQuery('end')) {
                        $filter['end'] = $this->params()->fromQuery('end');
                    }
                    $total = $this->em->getRepository('Application\Entity\Track')->getTotalTracksFavorites($userId, $filter);
                    $start = $this->start($page, $limit);

                    while ($start > $total) {
                        $start = $this->start(-- $page, $limit);
                    }

                    $tracks = $this->em->getRepository('Application\Entity\Track')->getTracksFavorites($userId, $limit, $start, $filter, $sortArr);

                    $tracksIds = [];
                    foreach ($tracks as $trEntry) {
                        $tracksIds[] = $trEntry['id'];
                    }

                    $tracks = $this->em->getRepository('Application\Entity\Track')->findBy([
                        'id' => $tracksIds
                    ]);
                    foreach ($tracks as $track) {
                        $downloaded = $this->em->getRepository('Application\Entity\Download')->findOneBy([
                            'User' => $user,
                            'Track' => $track
                        ]);
                        if ($downloaded) {
                            $success = true;
                        } else {
                            $quoteType = 'quote' . $track->getTrackType()->getName();

                            if ($quoteType == 'quotePromo' && $quotePromo > 0) {
                                $success = true;
                                $quoteSub = true;
                                $quotePromo --;
                            } elseif ($quoteType == 'quoteExclusive' && $quoteExclusive > 0) {
                                $success = true;
                                $quoteSub = true;
                                $quoteExclusive --;
                            } else {
                                $messages[] = 'Quote was expired';
                                $success = false;
                                break;
                            }
                        }
                    }
                    $quote = [
                        'quotePromo' => $quotePromo,
                        'quoteExclusive' => $quoteExclusive
                    ];
                } else {
                    $messages[] = 'Quote was expired ' . $expireDate->format('Y-m-d H:i') . ' ' . $now->format('Y-m-d H:i');
                }
            }
        } else {
            $messages[] = 'User is not authorized!';
        }
        $result['success'] = $success;
        $result['messages'] = implode(' ', $messages);
        $result['quoteSub'] = $quoteSub;
        $result['quote'] = $quote;

        return new JsonModel($result);
    }

    public function downloadFavoritesStreamAction()
    {
        $nameFilter = new \Zend\Filter\Word\SeparatorToDash();
        $request = $this->getRequest();
        $remoteAddr = $request->getServer('REMOTE_ADDR');
        $userId = null;
        $maxSize = 4;
        $maxSizeLimited = false;

        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;

            if ($userId) {
                $User = $this->em->getReference('Application\Entity\User', $userId);
                $filter = [
                    'user' => $userId,
                    'favorites' => 1
                ];
                $sort = $this->params()->fromQuery('sort', 'created-desc');
                $limit = (int) $this->params()->fromQuery('limit', 100);
                $page = (int) $this->params()->fromQuery('page', 1);
                $sortArr = explode('-', $sort);
                if (! in_array($sortArr[0], $this->sortList)) {
                    $sortArr = [
                        'release',
                        'desc'
                    ];
                }
                if (! in_array($sortArr[1], [
                    'asc',
                    'desc'
                ])) {
                    $sortArr[1] = 'desc';
                }
                if ($this->params()->fromQuery('artists')) {
                    $filter['artists'] = explode(',', $this->params()->fromQuery('artists'));
                }
                if ($this->params()->fromQuery('genre')) {
                    $filter['genre'] = (int) $this->params()->fromQuery('genre');
                }
                if ($this->params()->fromQuery('label')) {
                    $filter['label'] = (int) $this->params()->fromQuery('label');
                }
                if ($this->params()->fromQuery('type')) {
                    $filter['type'] = (int) $this->params()->fromQuery('type');
                }
                if ($this->params()->fromQuery('wav')) {
                    $filter['wav'] = (int) $this->params()->fromQuery('wav');
                }
                if ($this->params()->fromQuery('last')) {
                    $filter['last'] = $this->params()->fromQuery('last');
                }
                if ($this->params()->fromQuery('start')) {
                    $filter['start'] = $this->params()->fromQuery('start');
                }
                if ($this->params()->fromQuery('end')) {
                    $filter['end'] = $this->params()->fromQuery('end');
                }
                $total = $this->em->getRepository('Application\Entity\Track')->getTotalTracksFavorites($userId, $filter);
                $start = $this->start($page, $limit);

                while ($start > $total) {
                    $start = $this->start(-- $page, $limit);
                }

                $tracks = $this->em->getRepository('Application\Entity\Track')->getTracksFavorites($userId, $limit, $start, $filter, $sortArr);

                $tracksIds = [];
                foreach ($tracks as $trEntry) {
                    $tracksIds[] = $trEntry['id'];
                }
                $filter['trackIds'] = $tracksIds;

                $tracks = $this->em->getRepository('Application\Entity\Track')->getTracksForArchive($limit, 0, $filter);

                $zipName = date('d_m_Y') . '_archive_favorites_' . count($tracks) . '.zip';
                $contentArr = [];
                $bytes = 0;

                foreach ($tracks as $track) {
                    $size = $track['fileSize'];
                    $bytes += $size;
                    if ($this->formatSizeGb($bytes) > $maxSize) {
                        $maxSizeLimited = true;
                        break;
                    }
                    $extension = pathinfo($track['fileDestination'], PATHINFO_EXTENSION);
                    /* if($extension == 'mp4'){
                        $extension = 'stem.mp4';
                    } */
                    $fileName = $track['artists'] . ' - ' . $track['title'] . (($track['label']) ? ' [' . $track['label'] . ']' : '') . '.' . $extension;
                    $filePath = str_replace('public/', '/', $track['fileDestination']);
                    $crc32 = ($track['crc32']) ? $track['crc32'] : hash_file('crc32b', realpath($track['fileDestination']));
                    $contentArr[] = "$crc32 $size $filePath $fileName";

                    $Track = $this->em->getReference('Application\Entity\Track', $track['id']);

                    $downloaded = $this->em->getRepository('Application\Entity\Download')->findOneBy([
                        'User' => $User,
                        'Track' => $track
                    ]);
                    if (! $downloaded) {
                        $User->subQuote($track['type']);
                    }
                    $download = new Download($Track, $User);
                    $download->setIp($remoteAddr);
                    $this->em->persist($download);
                    $this->em->flush();
                }
                $content = implode("\r\n", $contentArr) . "\r\n";

                $response = $this->getResponse();
                $headers = $response->getHeaders();
                $headers->addHeaderLine("Content-Disposition: attachment; filename=\"" . $zipName . "\"");
                $headers->addHeaderLine("X-filename: " . $zipName);
                $headers->addHeaderLine("X-Archive-Files: zip");
                $headers->addHeaderLine("Cache-control: private");
                $headers->addHeaderLine("Content-encoding: none");
                $headers->addHeaderLine("Accept-Encoding: ''");
                if ($maxSizeLimited) {
                    $headers->addHeaderLine('X-CustomHeader: Archive has reached maximum allowed limit 4Gb, please filter results!');
                } else {
                    $headers->addHeaderLine('X-CustomHeader: ');
                }
                $response->setContent($content);
                return $this->getResponse();
            }
        }

        exit();
    }

    public function downloadFileAction()
    {
        $filter = new \Zend\Filter\Word\SeparatorToDash();
        $id = (int) $this->params()->fromRoute('id', 0);
        $request = $this->getRequest();
        $userId = null;
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
            if ($userId) {
                $track = $this->em->find('Application\Entity\Track', $id);
                $user = $this->em->find('Application\Entity\User', $userId);
                if ($track && $user) {
                    $filePath = $track->getFileDestination();
                    $fileFormat = $track->getFileFormat();
                    $fileName = $filter->filter($track->getTitle()) . '.' . (($fileFormat == 'mp4')?'stem.':''). pathinfo($filePath, PATHINFO_EXTENSION);
                    $response = $this->getResponse();
                    $headers = $response->getHeaders();
                    $headers->addHeaderLine("Content-type: " . $track->getFileType());
                    $headers->addHeaderLine("Content-Disposition: attachment; filename=\"" . $fileName . "\"");
                    $headers->addHeaderLine("X-filename: " . $fileName);
                    $headers->addHeaderLine("Content-length: " . $track->getFileSize());
                    $headers->addHeaderLine("Cache-control: private");

                    // Write file content
                    $fileContent = file_get_contents($filePath);
                    if ($fileContent != false) {
                        $response->setContent($fileContent);
                    } else {
                        // Set 500 Server Error status code
                        $this->getResponse()->setStatusCode(500);
                        return;
                    }

                    $downloaded = $this->em->getRepository('Application\Entity\Download')->findOneBy([
                        'User' => $user,
                        'Track' => $track
                    ]);
                    if (! $downloaded) {
                        $download = new Download($track, $user);
                        $this->em->persist($download);
                        $user->subQuote($track->getTrackType()
                            ->getName());
                        $this->em->flush();
                    }

                    // Return Response to avoid default view rendering
                    return $this->getResponse();
                }
            }
        }

        exit();
    }

    public function downloadFileStreamAction()
    {
        $filter = new \Zend\Filter\Word\SeparatorToDash();
        $id = (int) $this->params()->fromRoute('id', 0);
        $format = $this->params()->fromQuery('format');
        $request = $this->getRequest();
        $remoteAddr = $request->getServer('REMOTE_ADDR');
        $userId = null;
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
            if ($userId) {
                $track = $this->em->find('Application\Entity\Track', $id);
                $user = $this->em->find('Application\Entity\User', $userId);
                if ($track && $user) {
                    $filePath = $track->getFileDestination();
                    $fileContent = file_get_contents($filePath);
                    $fileFormat = $track->getFileFormat();
                    $contentType = $track->getFileType();
                    $contentLength = $track->getFileSize();
                    if ($fileContent != false) {
                        $response = new Stream();
                        if ($format == 'mp3') {
                            $mp3Path = $track->getFileDestinationMp3();
                            if ($mp3Path == null) {
                                $mp3Path = ImportManager::convertMp3($filePath);
                                $track->setFileDestinationMp3($mp3Path);
                                $this->em->flush($track);
                            }
                            $filePath = $mp3Path;
                            $contentType = mime_content_type($filePath);
                            $contentLength = filesize($filePath);
                        }
                        $response->setStream(fopen($filePath, 'r'));
                        $response->setStatusCode(200);
                        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                        if($extension == 'mp4'){
                            $extension = 'stem.mp4';
                        }
                        $fileName = $track->getNameDownload() . '.' . $extension;
                        $response->setStreamName($fileName);

                        $headers = new Headers();
                        $headers->addHeaders(array(
                            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                            'X-filename' => $fileName,
                            'Content-Type' => $contentType,
                            'Content-Length' => $contentLength,
                            'Cache-control' => 'private'
                        ));
                        $response->setHeaders($headers);
                    } else {
                        // Set 500 Server Error status code
                        $this->getResponse()->setStatusCode(500);
                        return;
                    }

                    $downloaded = $this->em->getRepository('Application\Entity\Download')->findOneBy([
                        'User' => $user,
                        'Track' => $track
                    ]);
                    if (! $downloaded) {
                        $user->subQuote($track->getTrackType()
                            ->getName());
                    }
                    $download = new Download($track, $user);
                    $download->setIp($remoteAddr);
                    $this->em->persist($download);
                    $this->em->flush();

                    // Return Response to avoid default view rendering
                    return $response;
                }
            }
        }

        exit();
    }

    public function downloadArchiveAction()
    {
        $filter = new \Zend\Filter\Word\SeparatorToDash();
        $request = $this->getRequest();
        $userId = 1;
        $maxSize = 100;
        // $this->checkAuthorization($request);
        // if(!empty($this->tokenPayload)){
        // $userId = $this->tokenPayload->id;
        if ($userId) {
            $tracks = $this->em->getRepository('Application\Entity\Track')->getAllDownloaded($userId);

            $i = 0;
            $zipName = date('d_m_Y') . '_tracks_' . count($tracks) . '.zip';
            $zip = new \ZipArchive();
            // $zip->open($zipName, \ZipArchive::CREATE);

            $tmp_file = tempnam('.', '');
            $zip->open($tmp_file, \ZipArchive::CREATE);

            foreach ($tracks as $track) {
                $fileName = $filter->filter($track['title']) . '.' . pathinfo($track['fileDestination'], PATHINFO_EXTENSION);
                $zip->addFile(realpath($track['fileDestination']), $fileName);
                if ($i ++ >= 1)
                    break;
            }
            $zip->close();

            $response = new Stream();
            $response->setStream(fopen($tmp_file, 'r'));
            $response->setStatusCode(200);
            $response->setStreamName($zipName);

            $headers = new Headers();
            $headers->addHeaders(array(
                'Content-Disposition' => 'attachment; filename="' . $zipName . '"',
                'X-filename' => $zipName,
                'Content-Type' => 'application/octet-stream',
                'Cache-control' => 'private'
            ));
            $response->setHeaders($headers);
            unlink($tmp_file);
            return $response;
        }
        // }

        exit();
    }

    public function downloadArchiveStreamAction()
    {
        $nameFilter = new \Zend\Filter\Word\SeparatorToDash();
        $request = $this->getRequest();
        $remoteAddr = $request->getServer('REMOTE_ADDR');
        $userId = null;
        $maxSize = 4;
        $maxSizeLimited = false;

        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;

            if ($userId) {
                $User = $this->em->getReference('Application\Entity\User', $userId);
                $filter = [
                    'user' => $userId
                ];
                $sort = $this->params()->fromQuery('sort', 'created-desc');
                $limit = (int) $this->params()->fromQuery('limit', 100);
                $page = (int) $this->params()->fromQuery('page', 1);
                $sortArr = explode('-', $sort);
                if (! in_array($sortArr[0], $this->sortList)) {
                    $sortArr = [
                        'release',
                        'desc'
                    ];
                }
                if (! in_array($sortArr[1], [
                    'asc',
                    'desc'
                ])) {
                    $sortArr[1] = 'desc';
                }
                if ($this->params()->fromQuery('artists')) {
                    $filter['artists'] = explode(',', $this->params()->fromQuery('artists'));
                }
                if ($this->params()->fromQuery('genre')) {
                    $filter['genre'] = (int) $this->params()->fromQuery('genre');
                }
                if ($this->params()->fromQuery('label')) {
                    $filter['label'] = (int) $this->params()->fromQuery('label');
                }
                if ($this->params()->fromQuery('type')) {
                    $filter['type'] = (int) $this->params()->fromQuery('type');
                }
                if ($this->params()->fromQuery('wav')) {
                    $filter['wav'] = (int) $this->params()->fromQuery('wav');
                }
                if ($this->params()->fromQuery('last')) {
                    $filter['last'] = $this->params()->fromQuery('last');
                }
                if ($this->params()->fromQuery('start')) {
                    $filter['start'] = $this->params()->fromQuery('start');
                }
                if ($this->params()->fromQuery('end')) {
                    $filter['end'] = $this->params()->fromQuery('end');
                }
                $total = $this->em->getRepository('Application\Entity\Track')->getTotalTracksDownloaded($userId, $filter);
                $start = $this->start($page, $limit);
                while ($start > $total) {
                    $start = $this->start(-- $page, $limit);
                }
                $tracks = $this->em->getRepository('Application\Entity\Track')->getDownloadedForArchive($userId, $limit, $start, $filter, $sortArr);

                $zipName = date('d_m_Y') . '_tracks_' . count($tracks) . '.zip';
                $contentArr = [];
                $bytes = 0;

                foreach ($tracks as $track) {
                    $size = $track['fileSize'];
                    $bytes += $size;
                    if ($this->formatSizeGb($bytes) > $maxSize) {
                        $maxSizeLimited = true;
                        break;
                    }
                    $extension = pathinfo($track['fileDestination'], PATHINFO_EXTENSION);
                    /* if($extension == 'mp4'){
                        $extension = 'stem.mp4';
                    } */
                    $fileName = $track['artists'] . ' - ' . $track['title'] . (($track['label']) ? ' [' . $track['label'] . ']' : '') . '.' . $extension;
                    $filePath = str_replace('public/', '/', $track['fileDestination']);
                    $crc32 = ($track['crc32']) ? $track['crc32'] : hash_file('crc32b', realpath($track['fileDestination']));
                    $contentArr[] = "$crc32 $size $filePath $fileName";

                    $Track = $this->em->getReference('Application\Entity\Track', $track['id']);
                    $download = new Download($Track, $User);
                    $download->setIp($remoteAddr);
                    $this->em->persist($download);
                    $this->em->flush($download);
                }
                $content = implode("\r\n", $contentArr) . "\r\n";

                $response = $this->getResponse();
                $headers = $response->getHeaders();
                $headers->addHeaderLine("Content-Disposition: attachment; filename=\"" . $zipName . "\"");
                $headers->addHeaderLine("X-filename: " . $zipName);
                $headers->addHeaderLine("X-Archive-Files: zip");
                $headers->addHeaderLine("Cache-control: private");
                $headers->addHeaderLine("Content-encoding: none");
                $headers->addHeaderLine("Accept-Encoding: ''");
                if ($maxSizeLimited) {
                    $headers->addHeaderLine('X-CustomHeader: Archive has reached maximum allowed limit 4Gb, please filter results!');
                } else {
                    $headers->addHeaderLine('X-CustomHeader: ');
                }
                $response->setContent($content);
                return $this->getResponse();
            }
        }

        exit();
    }

    public function downloadAlbumStreamAction()
    {
        $nameFilter = new \Zend\Filter\Word\SeparatorToDash();
        $request = $this->getRequest();
        $remoteAddr = $request->getServer('REMOTE_ADDR');
        $userId = null;
        $maxSize = 4;
        $maxSizeLimited = false;

        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;

            if ($userId) {
                $User = $this->em->getReference('Application\Entity\User', $userId);
                $filter = [
                    'user' => $userId
                ];
                $albumId = (int) $this->params()->fromRoute('id');

                $album = $this->em->find('Application\Entity\Album', $albumId);
                $tracks = $this->em->getRepository('Application\Entity\Track')->getTracksForArchive(100, 0, [
                    'album' => $albumId
                ]);

                $zipName = date('d_m_Y') . '_album_' . $nameFilter->filter($album->getName()) . '.zip';
                $contentArr = [];
                $bytes = 0;

                foreach ($tracks as $track) {
                    $size = $track['fileSize'];
                    $bytes += $size;
                    if ($this->formatSizeGb($bytes) > $maxSize) {
                        $maxSizeLimited = true;
                        break;
                    }
                    $extension = pathinfo($track['fileDestination'], PATHINFO_EXTENSION);
                    /* if($extension == 'mp4'){
                        $extension = 'stem.mp4';
                    } */
                    $fileName = $track['artists'] . ' - ' . $track['title'] . (($track['label']) ? ' [' . $track['label'] . ']' : '') . '.' . $extension;
                    $filePath = str_replace('public/', '/', $track['fileDestination']);
                    $crc32 = ($track['crc32']) ? $track['crc32'] : hash_file('crc32b', realpath($track['fileDestination']));
                    $contentArr[] = "$crc32 $size $filePath $fileName";

                    $Track = $this->em->getReference('Application\Entity\Track', $track['id']);

                    $downloaded = $this->em->getRepository('Application\Entity\Download')->findOneBy([
                        'User' => $User,
                        'Track' => $track
                    ]);
                    if (! $downloaded) {
                        $User->subQuote($track['type']);
                    }
                    $download = new Download($Track, $User);
                    $download->setIp($remoteAddr);
                    $this->em->persist($download);
                    $this->em->flush();
                }
                $content = implode("\r\n", $contentArr) . "\r\n";

                $response = $this->getResponse();
                $headers = $response->getHeaders();
                $headers->addHeaderLine("Content-Disposition: attachment; filename=\"" . $zipName . "\"");
                $headers->addHeaderLine("X-filename: " . $zipName);
                $headers->addHeaderLine("X-Archive-Files: zip");
                $headers->addHeaderLine("Cache-control: private");
                $headers->addHeaderLine("Content-encoding: none");
                $headers->addHeaderLine("Accept-Encoding: ''");
                if ($maxSizeLimited) {
                    $headers->addHeaderLine('X-CustomHeader: Archive has reached maximum allowed limit 4Gb, please filter results!');
                } else {
                    $headers->addHeaderLine('X-CustomHeader: ');
                }
                $response->setContent($content);
                return $this->getResponse();
            }
        }

        exit();
    }

    public function downloadArchiveStreamTestAction()
    {
        $nameFilter = new \Zend\Filter\Word\SeparatorToDash();
        $request = $this->getRequest();
        $userId = 1;
        $maxSize = 4;
        $maxSizeLimited = false;

        if ($userId) {
            $filter = [
                'user' => $userId
            ];
            $sort = $this->params()->fromQuery('sort', 'created-desc');
            $limit = (int) $this->params()->fromQuery('limit', 100);
            $page = (int) $this->params()->fromQuery('page', 1);
            $sortArr = explode('-', $sort);
            if (! in_array($sortArr[0], $this->sortList)) {
                $sortArr = [
                    'release',
                    'desc'
                ];
            }
            if (! in_array($sortArr[1], [
                'asc',
                'desc'
            ])) {
                $sortArr[1] = 'desc';
            }
            if ($this->params()->fromQuery('artists')) {
                $filter['artists'] = explode(',', $this->params()->fromQuery('artists'));
            }
            if ($this->params()->fromQuery('genre')) {
                $filter['genre'] = (int) $this->params()->fromQuery('genre');
            }
            if ($this->params()->fromQuery('label')) {
                $filter['label'] = (int) $this->params()->fromQuery('label');
            }
            if ($this->params()->fromQuery('type')) {
                $filter['type'] = (int) $this->params()->fromQuery('type');
            }
            if ($this->params()->fromQuery('wav')) {
                $filter['wav'] = (int) $this->params()->fromQuery('wav');
            }
            $total = $this->em->getRepository('Application\Entity\Track')->getTotalTracksDownloaded($userId, $filter);
            $start = $this->start($page, $limit);
            while ($start > $total) {
                $start = $this->start(-- $page, $limit);
            }
            $tracks = $this->em->getRepository('Application\Entity\Track')->getDownloadedForArchive($userId, $limit, $start, $filter, $sortArr);

            $zipName = date('d_m_Y') . '_tracks_' . count($tracks) . '.zip';
            $contentArr = [];
            $bytes = 0;

            foreach ($tracks as $track) {
                $size = $track['fileSize'];
                $bytes += $size;
                if ($this->formatSizeGb($bytes) > $maxSize) {
                    $maxSizeLimited = true;
                    break;
                }
                $fileName = $nameFilter->filter($track['title']) . '.' . pathinfo($track['fileDestination'], PATHINFO_EXTENSION);
                $filePath = str_replace('public/', '/', $track['fileDestination']);
                $crc32 = ($track['crc32']) ? $track['crc32'] : hash_file('crc32b', realpath($track['fileDestination']));
                $contentArr[] = "$crc32 $size $filePath $fileName";
            }
            $content = implode("\r\n", $contentArr) . "\r\n";

            $response = $this->getResponse();
            $headers = $response->getHeaders();
            $headers->addHeaderLine("Content-Disposition: attachment; filename=\"" . $zipName . "\"");
            $headers->addHeaderLine("X-filename: " . $zipName);
            $headers->addHeaderLine("X-Archive-Files: zip");
            $headers->addHeaderLine("Cache-control: private");
            $headers->addHeaderLine("Content-encoding: none");
            $headers->addHeaderLine("Accept-Encoding: ''");
            if ($maxSizeLimited) {
                $headers->addHeaderLine('X-CustomHeader: Archive has reached maximum allowed limit 4Gb, please filter results!');
            } else {
                $headers->addHeaderLine('X-CustomHeader: ');
            }
            $response->setContent($content);
            return $this->getResponse();
        }

        exit();
    }

    public function formatSizeGb($bytes)
    {
        return number_format($bytes / 1073741824, 2);
    }

    public function formatSizeMb($bytes)
    {
        return number_format($bytes / 1048576, 2);
    }

    public function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    public function downloadsAction()
    {
        $result = [];
        $userId = null;
        $request = $this->getRequest();
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
        }
        if (! $userId)
            return new JsonModel($result);

        $filter = [
            'user' => $userId
        ];
        $sort = $this->params()->fromQuery('sort', 'created-desc');
        $limit = (int) $this->params()->fromQuery('limit', 100);
        $page = (int) $this->params()->fromQuery('page', 1);
        $sortArr = explode('-', $sort);
        if (! in_array($sortArr[0], $this->sortList)) {
            $sortArr = [
                'release',
                'desc'
            ];
        }
        if (! in_array($sortArr[1], [
            'asc',
            'desc'
        ])) {
            $sortArr[1] = 'desc';
        }
        if ($this->params()->fromQuery('artists')) {
            $filter['artists'] = explode(',', $this->params()->fromQuery('artists'));
        }
        if ($this->params()->fromQuery('genre')) {
            $filter['genre'] = (int) $this->params()->fromQuery('genre');
        }
        if ($this->params()->fromQuery('label')) {
            $filter['label'] = (int) $this->params()->fromQuery('label');
        }
        if ($this->params()->fromQuery('type')) {
            $filter['type'] = (int) $this->params()->fromQuery('type');
        }
        if ($this->params()->fromQuery('wav')) {
            $filter['wav'] = (int) $this->params()->fromQuery('wav');
        }
        if ($this->params()->fromQuery('last')) {
            $filter['last'] = $this->params()->fromQuery('last');
        }
        if ($this->params()->fromQuery('start')) {
            $filter['start'] = $this->params()->fromQuery('start');
        }
        if ($this->params()->fromQuery('end')) {
            $filter['end'] = $this->params()->fromQuery('end');
        }
        $total = $this->em->getRepository('Application\Entity\Track')->getTotalTracksDownloaded($userId, $filter);

        $start = $this->start($page, $limit);

        while ($start > $total) {
            $start = $this->start(-- $page, $limit);
        }
        $tracks = $this->em->getRepository('Application\Entity\Track')->getTracksDownloaded($userId, $limit, $start, $filter, $sortArr);
        $result['total'] = (int) $total;
        $result['page'] = $page;
        $result['limit'] = $limit;
        $artists = $this->em->getRepository('Application\Entity\Track')->getArtists($filter);
        if (! empty($artists)) {
            foreach ($artists as $key => $artist) {
                $artists[$key]['checked'] = (isset($filter['artists']) && in_array($artist['id'], $filter['artists'])) ? 1 : 0;
            }
        }
        $result['artists'] = $artists;
        $result['types'] = $this->em->getRepository('Application\Entity\Track')->getTypes($filter);
        $result['labels'] = $this->em->getRepository('Application\Entity\Track')->getLabels($filter);
        $result['genres'] = $this->em->getRepository('Application\Entity\Track')->getGenres($filter);

        if (! empty($tracks)) {
            foreach ($tracks as $key => $track) {
                $tracks[$key]['artists'] = $this->em->getRepository('Application\Entity\Track')->getTrackArtists($track['id']);  
                $tracks[$key]['url'] = $this->static . str_replace('public/', '/', $track['sample']);
                unset($tracks[$key]['sample']);
                if (! $track['cover']){
                    $tracks[$key]['cover'] = $this->staticImg.'/music.png';
                }
                else{
                    $tracks[$key]['cover'] = $this->staticImg.'/400x400'. str_replace('public/media/img/', '/', $track['cover']);
                } 
                
                $tracks[$key]['release'] = $track['release']->format('Y-m-d');
                $tracks[$key]['downloaded'] = true;//$track['created']->format('d.m');
            }
            $result['tracks'] = $tracks;
        }
        return new JsonModel($result);
    }

    public function searchAction()
    {
        $result = [];
        $search = $this->params()->fromQuery('query');

        $artists = $this->em->getRepository('Application\Entity\Track')->searchArtistsArray($search);
        if (! empty($artists)) {
            $result['artists'] = $artists;
        }
        $labels = $this->em->getRepository('Application\Entity\Track')->searchLabelsArray($search);
        if (! empty($labels)) {
            $result['labels'] = $labels;
        }
        $albums = $this->em->getRepository('Application\Entity\Track')->searchAlbumsArray($search);
        if (! empty($albums)) {
            $result['albums'] = $albums;
        }
        $tracks = $this->em->getRepository('Application\Entity\Track')->searchTracksArray($search);
        if (! empty($tracks)) {
            $result['tracks'] = $tracks;
        }

        return new JsonModel($result);
    }

    public function searchResultsAction()
    {
        $result = [];
        $search = $this->params()->fromRoute('query');
        if ($search == '') {
            return new JsonModel($result);
        }

        $filter = [
            'search' => $search
        ];
        $sort = $this->params()->fromQuery('sort', 'release-desc');
        $limit = (int) $this->params()->fromQuery('limit', 100);
        $page = (int) $this->params()->fromQuery('page', 1);
        $filter['showPromo'] = $this->params()->fromQuery('showPromo', true);
        $sortArr = explode('-', $sort);
        if (! in_array($sortArr[0], $this->sortList)) {
            $sortArr = [
                'release',
                'desc'
            ];
        }
        if (! in_array($sortArr[1], [
            'asc',
            'desc'
        ])) {
            $sortArr[1] = 'desc';
        }

        $total = $this->em->getRepository('Application\Entity\Track')->getTotalTracks($filter);

        $start = $this->start($page, $limit);
        while ($start > $total) {
            $start = $this->start(-- $page, $limit);
        }

        $tracks = $this->em->getRepository('Application\Entity\Track')->getTracks($limit, $start, $filter, $sortArr);
        $result['total'] = (int) $total;
        $result['page'] = $page;
        $result['limit'] = $limit;
        $artists = $this->em->getRepository('Application\Entity\Track')->getArtists($filter);
        if (! empty($artists)) {
            foreach ($artists as $key => $artist) {
                $artists[$key]['checked'] = (isset($filter['artists']) && in_array($artist['id'], $filter['artists'])) ? 1 : 0;
            }
        }

        if (! empty($tracks)) {
            foreach ($tracks as $key => $track) {
                $tracks[$key]['artists'] = $this->em->getRepository('Application\Entity\Track')->getTrackArtists($track['id']);
                $tracks[$key]['url'] = $this->static . str_replace('public/', '/', $track['sample']);
                unset($tracks[$key]['sample']);
                if (! $track['cover']){
                    $tracks[$key]['cover'] = $this->staticImg.'/music.png';
                }
                else{
                    $tracks[$key]['cover'] = $this->staticImg.'/400x400'. str_replace('public/media/img/', '/', $track['cover']);
                } 
                $tracks[$key]['release'] = $track['release']->format('Y-m-d');
            }
            $result['tracks'] = $tracks;
        }

        return new JsonModel($result);
    }

    public function favoritesAction()
    {
        $result = [];
        $userId = null;
        $request = $this->getRequest();
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
        }
        if (! $userId)
            return new JsonModel($result);

        $filter = [
            'user' => $userId,
            'favorites' => 1
        ];
        $sort = $this->params()->fromQuery('sort', 'created-desc');
        $limit = (int) $this->params()->fromQuery('limit', 100);
        $page = (int) $this->params()->fromQuery('page', 1);
        $sortArr = explode('-', $sort);
        if (! in_array($sortArr[0], $this->sortList)) {
            $sortArr = [
                'release',
                'desc'
            ];
        }
        if (! in_array($sortArr[1], [
            'asc',
            'desc'
        ])) {
            $sortArr[1] = 'desc';
        }
        if ($this->params()->fromQuery('artists')) {
            $filter['artists'] = explode(',', $this->params()->fromQuery('artists'));
        }
        if ($this->params()->fromQuery('genre')) {
            $filter['genre'] = (int) $this->params()->fromQuery('genre');
        }
        if ($this->params()->fromQuery('label')) {
            $filter['label'] = (int) $this->params()->fromQuery('label');
        }
        if ($this->params()->fromQuery('type')) {
            $filter['type'] = (int) $this->params()->fromQuery('type');
        }
        if ($this->params()->fromQuery('wav')) {
            $filter['wav'] = (int) $this->params()->fromQuery('wav');
        }
        if ($this->params()->fromQuery('last')) {
            $filter['last'] = $this->params()->fromQuery('last');
        }
        if ($this->params()->fromQuery('start')) {
            $filter['start'] = $this->params()->fromQuery('start');
        }
        if ($this->params()->fromQuery('end')) {
            $filter['end'] = $this->params()->fromQuery('end');
        }
        $total = $this->em->getRepository('Application\Entity\Track')->getTotalTracksFavorites($userId, $filter);
        $start = $this->start($page, $limit);

        while ($start > $total) {
            $start = $this->start(-- $page, $limit);
        }

        $tracks = $this->em->getRepository('Application\Entity\Track')->getTracksFavorites($userId, $limit, $start, $filter, $sortArr);
        $result['total'] = (int) $total;
        $result['page'] = $page;
        $result['limit'] = $limit;
        $artists = $this->em->getRepository('Application\Entity\Track')->getArtists($filter);
        if (! empty($artists)) {
            foreach ($artists as $key => $artist) {
                $artists[$key]['checked'] = (isset($filter['artists']) && in_array($artist['id'], $filter['artists'])) ? 1 : 0;
            }
        }
        $result['artists'] = $artists;
        $result['types'] = $this->em->getRepository('Application\Entity\Track')->getTypes($filter);
        $result['labels'] = $this->em->getRepository('Application\Entity\Track')->getLabels($filter);
        $result['genres'] = $this->em->getRepository('Application\Entity\Track')->getGenres($filter);

        if (! empty($tracks)) {
            $trackIds = [];
            foreach ($tracks as $track){
                $trackIds[] = $track['id'];
            }
            $downloadedIds = $this->em->getRepository('Application\Entity\User')->getDownloadedIds($userId, $trackIds);
            foreach ($tracks as $key => $track) {
                $tracks[$key]['artists'] = $this->em->getRepository('Application\Entity\Track')->getTrackArtists($track['id']);
                $tracks[$key]['url'] = $this->static . str_replace('public/', '/', $track['sample']);
                unset($tracks[$key]['sample']);
                if (! $track['cover']){
                    $tracks[$key]['cover'] = $this->staticImg.'/music.png';
                }
                else{
                    $tracks[$key]['cover'] = $this->staticImg.'/400x400'. str_replace('public/media/img/', '/', $track['cover']);
                } 
                $tracks[$key]['release'] = $track['release']->format('Y-m-d');
                $tracks[$key]['downloaded'] = (in_array($track['id'], $downloadedIds));
                $tracks[$key]['isFavorite'] = true;
                
            }
            $result['tracks'] = $tracks;
        }
        return new JsonModel($result);
    }

    public function topAction()
    {
        $result = [];
        $userId = null;
        $downloadedIds = [];
        $request = $this->getRequest();
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
        }
        $filter = [
            'trackIds' => []
        ];
        $sort = $this->params()->fromQuery('sort', 'release-desc');
        $filter['showPromo'] = $this->params()->fromQuery('showPromo', true);
        $limit = (int) $this->params()->fromQuery('limit', 100);
        $sortArr = explode('-', $sort);
        if (! in_array($sortArr[0], $this->sortList)) {
            $sortArr = [
                'release',
                'desc'
            ];
        }
        if (! in_array($sortArr[1], [
            'asc',
            'desc'
        ])) {
            $sortArr[1] = 'desc';
        }
        if ($this->params()->fromQuery('artists')) {
            $filter['artists'] = explode(',', $this->params()->fromQuery('artists'));
        }
        if ($this->params()->fromQuery('genre')) {
            $filter['genre'] = (int) $this->params()->fromQuery('genre');
        }
        if ($this->params()->fromQuery('label')) {
            $filter['label'] = (int) $this->params()->fromQuery('label');
        }
        if ($this->params()->fromQuery('type')) {
            $filter['type'] = (int) $this->params()->fromQuery('type');
        }

        $tracks = $this->em->getRepository('Application\Entity\Track')->getTracksTop($limit, $filter, $sortArr);
        $trackIds = [];
        if (! empty($tracks)) {
            if($userId){
                $trackIds = [];
                foreach ($tracks as $track){
                    $trackIds[] = $track['id'];
                }
                $downloadedIds = $this->em->getRepository('Application\Entity\User')->getDownloadedIds($userId, $trackIds);
            }
            foreach ($tracks as $key => $track) {
                $tracks[$key]['artists'] = $this->em->getRepository('Application\Entity\Track')->getTrackArtists($track['id']);
                $tracks[$key]['url'] = $this->static . str_replace('public/', '/', $track['sample']);
                unset($tracks[$key]['sample']);
                if (! $track['cover']){
                    $tracks[$key]['cover'] = $this->staticImg.'/music.png';
                }
                else{
                    $tracks[$key]['cover'] = $this->staticImg.'/400x400'. str_replace('public/media/img/', '/', $track['cover']);
                }           
                $tracks[$key]['release'] = $track['release']->format('Y-m-d');
                $tracks[$key]['downloaded'] = (in_array($track['id'], $downloadedIds));
                $filter['trackIds'][] = $track['id'];
            }
            $result['tracks'] = $tracks;
        }

        $artists = $this->em->getRepository('Application\Entity\Track')->getArtists($filter);
        if (! empty($artists)) {
            foreach ($artists as $key => $artist) {
                $artists[$key]['checked'] = (isset($filter['artists']) && in_array($artist['id'], $filter['artists'])) ? 1 : 0;
            }
        }
        $result['artists'] = $artists;
        $result['types'] = $this->em->getRepository('Application\Entity\Track')->getTypes($filter);
        $result['labels'] = $this->em->getRepository('Application\Entity\Track')->getLabels($filter);
        $result['genres'] = $this->em->getRepository('Application\Entity\Track')->getGenres($filter);

        return new JsonModel($result);
    }

    public function addFavoriteAction()
    {
        $result = [];
        $success = false;
        $messages = [];
        $quote = [];
        $quoteSub = false;
        $id = (int) $this->params()->fromRoute('id', 0);
        $request = $this->getRequest();
        $userId = null;
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
            if ($userId) {
                $track = $this->em->find('Application\Entity\Track', $id);
                $user = $this->em->find('Application\Entity\User', $userId);
                if ($track && $user) {
                    $favorite = $this->em->getRepository('Application\Entity\Favorite')->findOneBy([
                        'User' => $user,
                        'Track' => $track
                    ]);
                    if ($favorite) {
                        $success = true;
                    } else {
                        $success = true;
                        $favorite = new Favorite($track, $user);
                        $this->em->persist($favorite);
                        $this->em->flush();
                    }
                } else {
                    $messages[] = 'Track or user wrong!';
                }
            }
        } else {
            $messages[] = 'User is not authorized!';
        }
        $result['success'] = $success;
        $result['messages'] = implode(' ', $messages);

        return new JsonModel($result);
    }

    public function removeFavoriteAction()
    {
        $result = [];
        $success = false;
        $messages = [];
        $quote = [];
        $quoteSub = false;
        $id = (int) $this->params()->fromRoute('id', 0);
        $request = $this->getRequest();
        $userId = null;
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
            if ($userId) {
                $track = $this->em->find('Application\Entity\Track', $id);
                $user = $this->em->find('Application\Entity\User', $userId);
                if ($track && $user) {
                    $favorite = $this->em->getRepository('Application\Entity\Favorite')->findOneBy([
                        'User' => $user,
                        'Track' => $track
                    ]);
                    if ($favorite) {
                        $this->em->remove($favorite);
                        $this->em->flush();
                        $success = true;
                    }
                } else {
                    $messages[] = 'Track or user wrong!';
                }
            }
        } else {
            $messages[] = 'User is not authorized!';
        }
        $result['success'] = $success;
        $result['messages'] = implode(' ', $messages);

        return new JsonModel($result);
    }

    public function clearFavoritesAction()
    {
        $result = [];
        $success = false;
        $messages = [];
        $request = $this->getRequest();
        $userId = null;
        $this->checkAuthorization($request);
        if (! empty($this->tokenPayload)) {
            $userId = $this->tokenPayload->id;
            if ($userId) {
                $user = $this->em->find('Application\Entity\User', $userId);
                if ($user) {
                    $favoritesClear = $this->em->getRepository('Application\Entity\User')->clearFavorites($user);
                    $success = true;
                } else {
                    $messages[] = 'User wrong!';
                }
            }
        } else {
            $messages[] = 'User is not authorized!';
        }
        $result['success'] = $success;
        $result['messages'] = implode(' ', $messages);

        return new JsonModel($result);
    }

    public function fromJson()
    {
        $body = $this->getRequest()->getContent();
        if (! empty($body)) {
            $json = json_decode($body, true);
            if (! empty($json)) {
                return $json;
            }
        }

        return false;
    }

    public function checkAuthorization($request)
    {
        $jwtToken = $this->findJwtToken($request);
        if ($jwtToken) {
            $this->token = $jwtToken;
            $this->decodeJwtToken();
            if (is_object($this->tokenPayload)) {
                return;
            }
            return true;
        } else {
            return false;
        }

        return false;
    }

    public function findJwtToken($request)
    {
        $jwtToken = $request->getHeaders("Authorization") ? $request->getHeaders("Authorization")->getFieldValue() : '';
        if ($jwtToken) {
            $jwtToken = trim(trim($jwtToken, "Bearer"), " ");
            return $jwtToken;
        }
        if ($request->isGet()) {
            $jwtToken = $request->getQuery('token');
        }
        if ($request->isPost()) {
            $jwtToken = $request->getPost('token');
        }
        return $jwtToken;
    }

    /**
     * contain user information for createing JWT Token
     */
    protected function generateJwtToken($payload)
    {
        if (! is_array($payload) && ! is_object($payload)) {
            $this->token = false;
            return false;
        }
        $this->tokenPayload = $payload;
        $cypherKey = $this->jwtAuth['cypherKey'];
        $tokenAlgorithm = $this->jwtAuth['tokenAlgorithm'];
        $this->token = JWT::encode($this->tokenPayload, $cypherKey, $tokenAlgorithm);
        return $this->token;
    }

    /**
     * contain encoded token for user.
     */
    protected function decodeJwtToken()
    {
        if (! $this->token) {
            $this->tokenPayload = false;
        }
        $cypherKey = $this->jwtAuth['cypherKey'];
        $tokenAlgorithm = $this->jwtAuth['tokenAlgorithm'];
        try {
            $decodeToken = JWT::decode($this->token, $cypherKey, [
                $tokenAlgorithm
            ]);
            $this->tokenPayload = $decodeToken;
        } catch (\Exception $e) {
            $this->tokenPayload = $e->getMessage();
        }
    }
}
