<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Application\Entity\Repository\PageRepository")
 * @ORM\Table(name="page")
 */
class Page
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
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="string", length=128, nullable=false)
     * @Gedmo\Slug(fields={"title"})
     */
    private $slug;

    /**
     * @ORM\Column(name="is_published",type="boolean", nullable=false)
     */
    private $isPublished = true;

    /**
     * @ORM\Column(name="is_payment",type="boolean", nullable=false)
     */
    private $isPayment = false;

    /**
     * @ORM\Column(name="is_payment_default",type="boolean", nullable=false)
     */
    private $isPaymentDefault = false;

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
     * @return the $content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     *
     * @return the $slug
     */
    public function getSlug()
    {
        return $this->slug;
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
     * @param field_type $title            
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     *
     * @param field_type $content            
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     *
     * @param field_type $slug            
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     *
     * @param boolean $isPublished            
     */
    public function setIsPublished($isPublished)
    {
        $this->isPublished = $isPublished;
    }

    /**
     *
     * @return the $isPayment
     */
    public function getIsPayment()
    {
        return $this->isPayment;
    }

    /**
     *
     * @param boolean $isPayment            
     */
    public function setIsPayment($isPayment)
    {
        $this->isPayment = $isPayment;
    }

    /**
     *
     * @return the $isPaymentDefault
     */
    public function getIsPaymentDefault()
    {
        return $this->isPaymentDefault;
    }

    /**
     *
     * @param boolean $isPaymentDefault            
     */
    public function setIsPaymentDefault($isPaymentDefault)
    {
        $this->isPaymentDefault = $isPaymentDefault;
    }

    public function __toString()
    {
        return sprintf('%s', $this->getTitle());
    }
}