<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Application\Entity\Repository\SettingRepository")
 * @ORM\Table(name="setting")
 */
class Setting
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
     * @ORM\Column(type="string", length=25, nullable=false, unique=true)
     */
    private $code;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $value;

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
     * @return the $code
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     *
     * @return the $value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     *
     * @param field_type $code            
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     *
     * @param field_type $value            
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return sprintf('%s', $this->getCode());
    }
}