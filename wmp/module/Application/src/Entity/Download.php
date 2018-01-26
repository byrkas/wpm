<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity
 * @ORM\Table(name="download", indexes={@ORM\Index(name="track_user", columns={"track_id", "user_id"}),
 * })
 */
class Download
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
     * @ORM\ManyToOne(targetEntity="Track")
     * @ORM\JoinColumn(name="track_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $Track;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $User;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $ip;

    public function __construct($Track, $User)
    {
        $this->Track = $Track;
        $this->User = $User;
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
     * @return the $Track
     */
    public function getTrack()
    {
        return $this->Track;
    }

    /**
     *
     * @return the $User
     */
    public function getUser()
    {
        return $this->User;
    }

    /**
     *
     * @param field_type $Track            
     */
    public function setTrack($Track)
    {
        $this->Track = $Track;
    }

    /**
     *
     * @param field_type $User            
     */
    public function setUser($User)
    {
        $this->User = $User;
    }

    /**
     *
     * @return the $ip
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     *
     * @param field_type $ip            
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    public function __toString()
    {
        return sprintf('%s', $this->getId());
    }
}