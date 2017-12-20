<?php  
namespace Application\Service;

use Zend\Session\SessionManager;
use Zend\Session\Container;
use Zend\View\Helper\FlashMessenger;
use \GetId3\GetId3Core as GetId3;
use Application\Entity\Label;
use Application\Entity\Track;
use Application\Entity\Album;
use Application\Entity\Artist;
use Doctrine\Common\Collections\ArrayCollection;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\FFMpeg;

class ImportManager
{     
    protected $objectManager;    
    protected $flashMessenger;
    protected $config;
    protected $importFolder;
    protected $saveCover;
    protected $saveAudio;
    protected $saveSample;
    
    public function __construct($config ,$em)
    {
        $this->config = $config;     
        $this->objectManager = $em;       
        $this->flashMessenger = new FlashMessenger();
        $this->importFolder = $this->config['media']['import'];  
        $this->saveCover = $this->config['media']['save']['cover'];  
        $this->saveAudio = $this->config['media']['save']['audio'];  
        $this->saveSample = $this->config['media']['save']['sample'];  
    }    
    
    public function getImportFolder()
    {
        return $this->importFolder;
    }
    
    public function getAudioInfo($filePath)
    {
        $getId3 = new GetId3(); 
       /*  $getId3->option_md5_data        = true;
        $getId3->option_md5_data_source = true;
        $getId3->encoding               = 'UTF-8'; */
        $audio = $getId3
            ->setOptionMD5Data(true)
            ->setOptionMD5DataSource(true)
            //->setEncoding('UTF-8')
           // ->setEncoding('ISO-8859-1')
            ->analyze($filePath);
           
       /*  unset($audio['comments']['picture']);
        echo "<pre>";
        var_dump($audio);die; */
        return $audio;
    }
    
    public function updateTrack($trackData, $id)
    {
        $fileDestination = $this->saveFile($trackData);
        if(!$fileDestination)
            return false;
        
        $Track = $this->objectManager->find('Application\Entity\Track',$id);
        $data['fileDestination'] = $fileDestination;
        if(isset($trackData['picture'])){
            $cover = $this->saveCover($trackData);
            if($cover)
                $data['cover']  =   $cover;
        }
        $data['TrackType'] = $this->getTrackType($trackData['type']);
        if(isset($trackData['album'])){
            $data['Album'] = $this->getAlbum($trackData['album'], $trackData['publishDate']);
        }

        $data['sampleDestination'] = $this->createSample($trackData);
        $data['wave'] = $this->createWave($trackData,$data['sampleDestination']);
        $data['crc32'] = hash_file('crc32b', realpath($data['fileDestination']));
        
        $Track->exchangeArray($data);
        $this->objectManager->flush();
        
        unlink($trackData['filePath']);
        return $Track;
    }
    
    
    public function getExtension($filePath)
    {
        return pathinfo($filePath, PATHINFO_EXTENSION);
    }
    
    public function createTrack($trackData)
    {
        $fileDestination = $this->saveFile($trackData);
        if(!$fileDestination)
            return false;        
        
        $Track = new Track();
        $data = $trackData;
        $data['fileDestination'] = $fileDestination;
        
        if(isset($trackData['picture'])){
            $cover = $this->saveCover($trackData);
            if($cover)
                $data['cover']  =   $cover;
        }
        $data['Label'] =  $this->getLabel($trackData['label']);
        $data['Genre']  =   $this->getGenre($trackData['genre']);
        $data['TrackType'] = $this->getTrackType($trackData['type']);
        if(isset($trackData['album'])){
            $data['Album'] = $this->getAlbum($trackData['album'], $trackData['publishDate']);
        }        
        $artistsData = $this->getArtists($trackData['artists_string']);
        $data['Artists'] = $artistsData['Artists'];
        $data['artistsString'] = $artistsData['string'];        
        $data['sampleDestination'] = $this->createSample($trackData);
        $data['wave'] = $this->createWave($trackData,$data['sampleDestination']);
        $data['crc32'] = hash_file('crc32b', realpath($data['fileDestination']));
        
        $Track->exchangeArray($data);
        $this->objectManager->persist($Track);
        $this->objectManager->flush();
        
        unlink($trackData['filePath']);
        return $Track;
    }
    
    public function getArtists($artistsString)
    {
        $template = $this->objectManager->getRepository('Application\Entity\Track')->getSettingValue('artists');
        $templateArr = explode(';', str_replace('.', '\.', strtolower($template)));
        foreach ($templateArr as $key => $tEntry){
            if($tEntry == ''){
                unset($templateArr[$key]);
            }
        }
        $re = '/\s?('.implode('|', $templateArr).')\s/i';
        $newStr = preg_replace($re,'|',$artistsString);
        
        $artistsArr = explode('|', $newStr);        
        
        $Artists = new ArrayCollection();
        foreach ($artistsArr as $artEntry){
            $name = trim($artEntry);
            $Artist = $this->objectManager->getRepository('Application\Entity\Artist')->findOneBy(['name' => $name]);
            if(!$Artist){
                $Artist = new Artist($name);
                $this->objectManager->persist($Artist);
                $this->objectManager->flush($Artist);
            }
            $Artists[] = $Artist;
            $artistsString = str_replace($name, '{'.$Artist->getId().'}', $artistsString);
        }
        
        return ['string' => $artistsString,'Artists' => $Artists];        
    }
    
    public function getLabel($name)
    {
        $Label = $this->objectManager->getRepository('Application\Entity\Track')->searchLabel($name);
        if(!$Label){
            $Label = new Label($name);
            $this->objectManager->persist($Label);
            $this->objectManager->flush($Label);
        }else{
            if(stripos($name, 'record') !== FALSE  && stripos($Label->getName(), ' record') === FALSE )
            {
                $Label->setName($name);
            }
        }
        
        return $Label;
    }
    
    public function getTrackType($name)
    {
        return $this->objectManager->getRepository('Application\Entity\TrackType')->findOneBy(['name' => $name]);
    }
    
    public function getAlbum($name, $date){
        $Album = $this->objectManager->getRepository('Application\Entity\Album')->findOneBy(['name' => $name,'date' => $date]);
        if(!$Album){
            $Album = new Album($name, $date);
            $this->objectManager->persist($Album);
            $this->objectManager->flush($Album);
        }
        return $Album;
    }
    
    public function getGenre($genre)
    {
        return $this->objectManager->getRepository('Application\Entity\Genre')->findOneBy(['title' => $genre]);
    }
    
    public function saveCover($track)
    {
        $coverPath = $this->saveCover.'/'.$track['year'].'/'.$track['month_number'].'/'.$track['day'];
        if (!file_exists($coverPath)) {
            mkdir($coverPath, 0777, true);
        }
        $extension = ($track['image_mime'] == 'image/jpeg')?'.jpg':'.png';
        $coverPath = $coverPath.'/cover_'.md5($track['title']).$extension;
        if(!file_put_contents($coverPath, $track['picture']['data'])){
            return false;
        }        
        
        return $coverPath;
    }
    
    public function saveFile($track)
    {
        $filePath = $this->saveAudio.'/'.$track['year'].'/'.$track['month_number'].'/'.$track['day'];
        if (!file_exists($filePath)) {
            mkdir($filePath, 0777, true);
        }
        $filePath = $filePath.'/track_'.md5($track['title']).'.'.$this->getExtension($track['filePath']);
        if (!copy($track['filePath'], $filePath)) {
            return false;
        }
        
        return $filePath;
    }
    
    public function tracksFromStructure($structure)
    {
        $mapping = ['year','month_number-month_name','day-type','genre'];
        $tracks = [];
        
        foreach ($structure as $value){
            $track = str_replace($this->importFolder.'/', '', $value);
            $trackParts = explode('/',$track);
            $trackEntry = [
                'filePath' => $value,
            ];
            foreach ($mapping as $key => $val){
                if(isset($trackParts[$key])){
                    if(strpos($val, '-') !== FALSE){
                        $keyExplode = explode('-',$val);
                        $valExplode = explode('-', $trackParts[$key]);
                        foreach ($keyExplode as $keyE => $valE){
                            $trackEntry[$valE] = $valExplode[$keyE];
                        }
                    }else{
                        $trackEntry[$val] = $trackParts[$key];
                    }
                }
            }
            $tracks[] = $trackEntry;
        }
        
        return $tracks;
    }
    
    public function validateTrack($track, $otherTracks = [])
    {
        $errors = [];
        $warnings = [];
        $Label = null;
        $Artitst = [];
        $track['publishDate'] = \DateTime::createFromFormat('Y-m-d H:i', $track['year'].'-'.$track['month_number'].'-'.$track['day'].' 00:00');
                
        if(!isset($track['picture'])){
            $warnings['picture'] = 'Cover not exist!';
        }
        
        if(!isset($track['label'])){
            $errors['label'] = 'Label is empty!';
        }else{
            $Label = $this->getLabel($track['label']);            
        }       
        if(!isset($track['artists_string'])){
            $errors['artists_string'] = 'Artist is empty!';
        }else{
            $ArtistsData = $this->getArtists($track['artists_string']);
            $Artitsts = $ArtistsData['Artists'];          
        }     
        if(!isset($track['title'])){
            $errors['title'] = 'Title is empty!';
        }
        
        $genreExist = $this->objectManager->getRepository('Application\Entity\Genre')->findOneBy(['title' => $track['genre']]);
        if(!$genreExist){
            $errors['genre'] = 'Genre '.$track['genre'].' not found!';
        }

        if(!empty($Artitsts) && $Label !== null){
            $trackExist = $this->objectManager->getRepository('Application\Entity\Track')->checkTrackExist($track['title'], $Label, $Artitsts);
            if($trackExist){
                if($trackExist->getFileFormat() == $track['fileFormat'])
                    $errors['trackExist'] = 'Track already exist!';
                elseif($trackExist->getFileFormat() == 'mp3' && $track['fileFormat'] == 'riff'){
                    $warnings['trackExist'] = 'Track already exist in format '.$trackExist->getFileFormat().'!';
                    $warnings['trackExistId'] = $trackExist->getId();
                }elseif($trackExist->getFileFormat() == 'riff' && $track['fileFormat'] == 'mp3'){
                    $errors['trackExist'] = 'Track already exist in format '.$trackExist->getFileFormat().'!';
                }
            }
        }
        
        if(!empty($otherTracks)){
            foreach ($otherTracks as $key => $oTEntry){
                $otherTrack = $oTEntry['track'];
                if($track['title'] == $otherTrack['title'] && $track['label'] == $otherTrack['label'] && $track['artists_string'] == $otherTrack['artists_string']){
                    $errors['trackExist'] = 'Track is the same as track #'.($key+1).' !';
                }
            }
        }
        
        return ['track' => $track, 'errors' => $errors, 'warnings' => $warnings];
    }
    
    public function parseImportFolder()
    {
        $structure = $this->scanDirectories($this->importFolder);
        $tracks = $this->tracksFromStructure($structure);
        foreach ($tracks as $key => $track){
            $audioInfo = $this->getAudioInfo($track['filePath']);
            if(isset($audioInfo['error'])){
                $track['error'] = $audioInfo['error'];
            }else{
                $tracks[$key]['genre'] = str_replace('@', '/', $track['genre']);
                if(isset($audioInfo['bitrate']))
                    $tracks[$key]['bitrate'] = $audioInfo['bitrate'];
                if(isset($audioInfo['mime_type']))
                    $tracks[$key]['fileType'] = $audioInfo['mime_type'];
                if(isset($audioInfo['filesize']))
                    $tracks[$key]['fileSize'] = $audioInfo['filesize'];
                if(isset($audioInfo['fileformat']))
                    $tracks[$key]['fileFormat'] = $audioInfo['fileformat'];
                if(isset($audioInfo['playtime_string']))
                    $tracks[$key]['playtimeString'] = $audioInfo['playtime_string'];
                if(isset($audioInfo['playtime_seconds']))
                    $tracks[$key]['playtimeSeconds'] = $audioInfo['playtime_seconds'];
                if(isset($audioInfo['tags']['id3v2'])){
                    if(isset($audioInfo['tags']['id3v2']['album']))
                        $tracks[$key]['label'] = $audioInfo['tags']['id3v2']['album'][0];
                    if(isset($audioInfo['tags']['id3v2']['artist']))
                        $tracks[$key]['artists_string'] = $audioInfo['tags']['id3v2']['artist'][0];
                    if(isset($audioInfo['tags']['id3v2']['original_album']))
                        $tracks[$key]['album'] = $audioInfo['tags']['id3v2']['original_album'][0];
                    if(isset($audioInfo['tags']['id3v2']['title']))
                        $tracks[$key]['title'] = $audioInfo['tags']['id3v2']['title'][0];
                }elseif(isset($audioInfo['tags']['riff'])){
                    if(isset($audioInfo['tags']['riff']['title']))
                        $tracks[$key]['title'] = $audioInfo['tags']['riff']['title'][0];
                    if(isset($audioInfo['tags']['riff']['artist']))
                        $tracks[$key]['artists_string'] = $audioInfo['tags']['riff']['artist'][0];
                    if(isset($audioInfo['tags']['riff']['product']))
                        $tracks[$key]['label'] = $audioInfo['tags']['riff']['product'][0];
                }elseif(isset($audioInfo['tags']['quicktime'])){
                    if(isset($audioInfo['tags']['quicktime']['album']))
                        $tracks[$key]['label'] = $audioInfo['tags']['quicktime']['album'][0];
                    if(isset($audioInfo['tags']['quicktime']['artist']))
                        $tracks[$key]['artists_string'] = $audioInfo['tags']['quicktime']['artist'][0];
                    if(isset($audioInfo['tags']['quicktime']['original_album']))
                        $tracks[$key]['album'] = $audioInfo['tags']['quicktime']['original_album'][0];
                    if(isset($audioInfo['tags']['quicktime']['title']))
                        $tracks[$key]['title'] = $audioInfo['tags']['quicktime']['title'][0];
                }
                if(isset($audioInfo['comments']['picture'][0]))
                    $tracks[$key]['picture'] = $audioInfo['comments']['picture'][0];
                
            }
        }
        
        return $tracks;
    }
    
    public function scanDirectories($rootDir, $allData=array()) {
        $invisibleFileNames = array(".", "..", ".htaccess", ".htpasswd");
        $dirContent = scandir($rootDir);
        foreach($dirContent as $key => $content) {
            $path = $rootDir.'/'.$content;
            if(!in_array($content, $invisibleFileNames)) {
                if(is_file($path) && is_readable($path)) {
                    $allData[] = $path;
                }elseif(is_dir($path) && is_readable($path)) {
                    $allData = $this->scanDirectories($path, $allData);
                }
            }
        }
        return $allData;
    }
    
    public function createWave($track, $sample, $showErrors = false)
    {
        $wavePath = $this->saveCover.'/'.$track['year'].'/'.$track['month_number'].'/'.$track['day'];
        if (!file_exists($wavePath)) {
            mkdir($wavePath, 0777, true);
        }
        $wavePath = $wavePath.'/wave_'.md5($track['title']).'.png';
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            'timeout'          => 3600, // The timeout for the underlying process
            'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
        ]);        
        $audio = $ffmpeg->open($sample);
        //color #cbcbcb; 203,203,203 //transparent
        
        try{
        // Create the waveform
            $waveform = $audio->waveform();
            $waveform->save($wavePath);
        
            return $wavePath;
        }catch (\Exception $e){
            if($showErrors){
                return ['error' => $e->getMessage()];
            }
            return null;
        }
    }
    
    public function createSample($track)
    {
        $filePath = $this->saveSample.'/'.$track['year'].'/'.$track['month_number'].'/'.$track['day'];
        if (!file_exists($filePath)) {
            mkdir($filePath, 0777, true);
        }
        $filePath = $filePath.'/sample_'.md5($track['title']).'.mp3';
        
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            'timeout'          => 3600, // The timeout for the underlying process
            'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
        ]);
        $format = new \FFMpeg\Format\Audio\Mp3();
        $audio = $ffmpeg->open($track['filePath']);
        $filter = new \FFMpeg\Filters\Audio\AudioClipFilter(\FFMpeg\Coordinate\TimeCode::fromSeconds(30), \FFMpeg\Coordinate\TimeCode::fromSeconds(120));
        $audio->addFilter($filter);
        $audio->save($format,$filePath);
        
        return $filePath;
    }
    
    static public function convertMp3($filePath)
    {
        $convertedPath = str_replace('.wav', '.mp3', $filePath);
        
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            'timeout'          => 3600, // The timeout for the underlying process
            'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
        ]);
        $format = new \FFMpeg\Format\Audio\Mp3();
        $audio = $ffmpeg->open($filePath);
        $audio->save($format,$convertedPath);
        
        return $convertedPath;
    }
        
}