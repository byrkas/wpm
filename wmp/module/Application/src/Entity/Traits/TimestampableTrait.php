<?php
namespace Application\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait TimestampableTrait
{

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    private $updated;

    /**
     *
     * @return the $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     *
     * @return the $updated
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     *
     * @param \DateTime $created            
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     *
     * @param \DateTime $updated            
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }
}