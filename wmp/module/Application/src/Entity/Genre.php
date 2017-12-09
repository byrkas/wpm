<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Application\Entity\Repository\GenreRepository")
 * @ORM\Table(name="genre")
 */
class Genre
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
     * @ORM\Column(type="string", length=100, nullable=false, unique=true)
     */
    private $title;

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
     * @return the $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     *
     * @param field_type $title            
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function __toString()
    {
        return sprintf('%s', $this->getTitle());
    }
}