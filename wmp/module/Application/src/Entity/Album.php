<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="album")
 */
class Album
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
    private $name;

    /**
     * @ORM\Column(name="date",type="date", nullable=true)
     */
    private $date;

    public function __construct($name, $date)
    {
        $this->name = $name;
        $this->date = $date;
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
     * @return the $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @return the $date
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     *
     * @param field_type $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    public function __toString()
    {
        return sprintf('%s', $this->getName());
    }
}