<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Application\Entity\Repository\BanIpRepository")
 * @ORM\Table(name="ban_ip")
 */
class BanIp
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
     * @ORM\Column(type="string", length=20, nullable=false, unique=true)
     */
    private $ip;

    public function __construct()
    {}

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
        return sprintf('%s', $this->getIp());
    }
}