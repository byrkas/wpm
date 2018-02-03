<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass="Application\Entity\Repository\ArtistRepository")
 * @ORM\Table(name="artist")
 */
class Artist
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
     * @ORM\Column(type="string", length=100, nullable=false, unique=true, options={"collation":"utf8_general_ci"})
     */
    private $name;

    /**
     * @ORM\Column(name="name_translit", type="string", length=100, nullable=true, options={"collation":"utf8_general_ci"})
     */
    private $nameTranslit;

    /**
     * @ORM\ManyToMany(targetEntity="Track", mappedBy="Artists",fetch="EXTRA_LAZY")
     */
    private $Tracks;

    public function __construct($name)
    {
        $this->name = $name;
        $nameTranslit = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        $this->nameTranslit = $nameTranslit;
        $this->Tracks = new ArrayCollection();
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
     * @return the $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @param field_type $name            
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     *
     * @return the $nameTranslit
     */
    public function getNameTranslit()
    {
        return $this->nameTranslit;
    }

    /**
     *
     * @param field_type $nameTranslit            
     */
    public function setNameTranslit($nameTranslit)
    {
        $this->nameTranslit = $nameTranslit;
    }

    /**
     *
     * @return the $Tracks
     */
    public function getTracks()
    {
        return $this->Tracks;
    }

    public function addTracks(Collection $Tracks)
    {
        foreach ($Tracks as $track) {
            if (! $this->getTracks()->contains($track)) {
                $this->Tracks[] = $track;
            }
        }
    }

    public function removeTracks(Collection $Tracks)
    {
        foreach ($Tracks as $track) {
            if ($this->getTracks()->contains($track)) {
                $this->getTracks()->removeElement($track);
            }
        }
    }

    public function __toString()
    {
        return sprintf('%s', $this->getName());
    }
}