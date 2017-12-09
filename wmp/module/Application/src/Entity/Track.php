<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass="Application\Entity\Repository\TrackRepository")
 * @ORM\Table(name="track")
 * @ORM\HasLifecycleCallbacks
 */
class Track
{
    use Traits\MagicTrait;
    use Traits\TimestampableTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    private $title;

    /**
     * @ORM\Column(name="crc32", type="string", length=32, nullable=true)
     */
    private $crc32;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cover;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $wave;

    /**
     * @ORM\Column(name="file_format",type="string", length=10, nullable=true)
     */
    private $fileFormat;

    /**
     * @ORM\Column(name="file_size", type="string", length=20, nullable=true)
     */
    private $fileSize;

    /**
     * @ORM\Column(name ="file_type", type="string", length=20, nullable=false)
     */
    private $fileType;

    /**
     * @ORM\Column(name ="bitrate", type="integer", nullable=true)
     */
    private $bitrate;

    /**
     * @ORM\Column(name ="playtime_string", type="string", length=10, nullable=true)
     */
    private $playtimeString;

    /**
     * @ORM\Column(name ="playtime_seconds", type="float", nullable=true)
     */
    private $playtimeSeconds;

    /**
     * @ORM\Column(name="file_destination",type="string", length=255, nullable=false)
     */
    private $fileDestination;

    /**
     * @ORM\Column(name="sample_destination",type="string", length=255, nullable=true)
     */
    private $sampleDestination;

    /**
     * @ORM\ManyToMany(targetEntity="Artist", inversedBy = "Tracks", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="track_artist",
     * joinColumns={@ORM\JoinColumn(name="track_id", referencedColumnName="id" ,onDelete = "CASCADE")},
     * inverseJoinColumns={@ORM\JoinColumn(name="artist_id", referencedColumnName="id",onDelete = "CASCADE")}
     * )
     */
    private $Artists;

    /**
     * @ORM\Column(name ="artists_string", type="string", length=200, nullable=true)
     */
    private $artistsString;

    /**
     * @ORM\ManyToOne(targetEntity="Genre")
     * @ORM\JoinColumn(name="genre_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $Genre;

    /**
     * @ORM\ManyToOne(targetEntity="Album")
     * @ORM\JoinColumn(name="album_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $Album;

    /**
     * @ORM\ManyToOne(targetEntity="Label")
     * @ORM\JoinColumn(name="label_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $Label;

    /**
     * @ORM\Column(name="publish_date",type="datetime", nullable=true)
     */
    private $publishDate;

    /**
     * @ORM\ManyToOne(targetEntity="TrackType")
     * @ORM\JoinColumn(name="track_type_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $TrackType;

    /**
     * @ORM\Column(name="is_published",type="boolean", nullable=false)
     */
    private $isPublished = false;

    public function __construct()
    {
        $this->Artists = new ArrayCollection();
    }

    /**
     * @ORM\PreRemove()
     */
    public function removeFiles()
    {
        @unlink(realpath($this->getFileDestination()));
        if ($this->getCover())
            @unlink(realpath($this->getCover()));
        if ($this->getWave())
            @unlink(realpath($this->getWave()));
    }

    /**
     *
     * @return the $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @return the $Artists
     */
    public function getArtists()
    {
        return $this->Artists;
    }

    public function setArtists($Artists)
    {
        $this->Artists = $Artists;
    }

    public function addArtists(Collection $Artists)
    {
        foreach ($Artists as $artist) {
            if (! $this->getArtists()->contains($artist)) {
                $this->Artists[] = $artist;
                $artist->addTracks(new ArrayCollection([
                    $this
                ]));
            }
        }
    }

    public function removeArtists(Collection $Artists)
    {
        foreach ($Artists as $artist) {
            if ($this->getArtists()->contains($artist)) {
                $this->getArtists()->removeElement($artist);
                $artist->removeTracks(new ArrayCollection([
                    $this
                ]));
            }
        }
    }

    /**
     *
     * @return the $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     *
     * @return the $Genre
     */
    public function getGenre()
    {
        return $this->Genre;
    }

    /**
     *
     * @return the $Album
     */
    public function getAlbum()
    {
        return $this->Album;
    }

    /**
     *
     * @param field_type $title            
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     *
     * @param field_type $Genre            
     */
    public function setGenre($Genre)
    {
        $this->Genre = $Genre;
    }

    /**
     *
     * @param field_type $Album            
     */
    public function setAlbum($Album)
    {
        $this->Album = $Album;
    }

    /**
     *
     * @return the $cover
     */
    public function getCover()
    {
        return $this->cover;
    }

    /**
     *
     * @return the $fileFormat
     */
    public function getFileFormat()
    {
        return $this->fileFormat;
    }

    /**
     *
     * @return the $fileSize
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     *
     * @return the $fileType
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     *
     * @return the $bitrate
     */
    public function getBitrate()
    {
        return $this->bitrate;
    }

    /**
     *
     * @return the $playtimeString
     */
    public function getPlaytimeString()
    {
        return $this->playtimeString;
    }

    /**
     *
     * @return the $fileDestination
     */
    public function getFileDestination()
    {
        return $this->fileDestination;
    }

    /**
     *
     * @return the $sampleDestination
     */
    public function getSampleDestination()
    {
        return $this->sampleDestination;
    }

    /**
     *
     * @param field_type $cover            
     */
    public function setCover($cover)
    {
        $this->cover = $cover;
    }

    /**
     *
     * @param field_type $fileFormat            
     */
    public function setFileFormat($fileFormat)
    {
        $this->fileFormat = $fileFormat;
    }

    /**
     *
     * @param field_type $fileSize            
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;
    }

    /**
     *
     * @param field_type $fileType            
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;
    }

    /**
     *
     * @param field_type $bitrate            
     */
    public function setBitrate($bitrate)
    {
        $this->bitrate = $bitrate;
    }

    /**
     *
     * @param field_type $playtimeString            
     */
    public function setPlaytimeString($playtimeString)
    {
        $this->playtimeString = $playtimeString;
    }

    /**
     *
     * @param field_type $fileDestination            
     */
    public function setFileDestination($fileDestination)
    {
        $this->fileDestination = $fileDestination;
    }

    /**
     *
     * @param field_type $sampleDestination            
     */
    public function setSampleDestination($sampleDestination)
    {
        $this->sampleDestination = $sampleDestination;
    }

    /**
     *
     * @return the $publishDate
     */
    public function getPublishDate()
    {
        return $this->publishDate;
    }

    /**
     *
     * @return the $TrackType
     */
    public function getTrackType()
    {
        return $this->TrackType;
    }

    /**
     *
     * @param field_type $publishDate            
     */
    public function setPublishDate($publishDate)
    {
        $this->publishDate = $publishDate;
    }

    /**
     *
     * @param field_type $TrackType            
     */
    public function setTrackType($TrackType)
    {
        $this->TrackType = $TrackType;
    }

    /**
     *
     * @return the $Label
     */
    public function getLabel()
    {
        return $this->Label;
    }

    /**
     *
     * @param field_type $Label            
     */
    public function setLabel($Label)
    {
        $this->Label = $Label;
    }

    /**
     *
     * @return the $playtimeSeconds
     */
    public function getPlaytimeSeconds()
    {
        return $this->playtimeSeconds;
    }

    /**
     *
     * @return the $artistsString
     */
    public function getArtistsString()
    {
        return $this->artistsString;
    }

    /**
     *
     * @param field_type $playtimeSeconds            
     */
    public function setPlaytimeSeconds($playtimeSeconds)
    {
        $this->playtimeSeconds = $playtimeSeconds;
    }

    /**
     *
     * @param field_type $artistsString            
     */
    public function setArtistsString($artistsString)
    {
        $this->artistsString = $artistsString;
    }

    /**
     *
     * @return the $wave
     */
    public function getWave()
    {
        return $this->wave;
    }

    /**
     *
     * @param field_type $wave            
     */
    public function setWave($wave)
    {
        $this->wave = $wave;
    }

    /**
     *
     * @return the $crc32
     */
    public function getCrc32()
    {
        return $this->crc32;
    }

    /**
     *
     * @param field_type $crc32            
     */
    public function setCrc32($crc32)
    {
        $this->crc32 = $crc32;
    }

    /**
     *
     * @return the $isPublished
     */
    public function getIsPublished()
    {
        return $this->isPublished;
    }

    /**
     *
     * @param boolean $isPublished            
     */
    public function setIsPublished($isPublished)
    {
        $this->isPublished = $isPublished;
    }

    public function __toString()
    {
        return sprintf('%s', $this->getTitle());
    }
}