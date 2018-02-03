<?php 
namespace Application\Service;

use FFMpeg\Format\Audio\DefaultAudio;

class Shine extends DefaultAudio
{
    public function __construct()
    {
        $this->audioCodec = 'libshine';
    }
    
    /**
     * {@inheritDoc}
     */
    public function getAvailableAudioCodecs()
    {
        return array('libshine');
    }
}
