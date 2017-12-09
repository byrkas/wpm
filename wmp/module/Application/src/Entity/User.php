<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Application\Entity\Repository\UserRepository")
 * @ORM\Table(name="user")
 */
class User
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
     * @ORM\Column(type="string", length=45, nullable=false, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(name="expire_date",type="datetime", nullable=true)
     */
    private $expireDate;

    /**
     * @ORM\Column(name="show_promo",type="boolean", nullable=false)
     */
    private $showPromo = true;

    /**
     * @ORM\Column(name="quote_promo", type="integer", nullable=false)
     */
    private $quotePromo = 0;

    /**
     * @ORM\Column(name="quote_exclusive", type="integer", nullable=false)
     */
    private $quoteExclusive = 0;

    /**
     * @ORM\ManyToOne(targetEntity="Page", cascade={"persist"})
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $PaymentPage;

    /**
     * @ORM\Column(name="active",type="boolean", nullable=false)
     */
    private $active = true;

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
     * @return the $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     *
     * @return the $password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     *
     * @param field_type $email            
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     *
     * @param field_type $password            
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     *
     * @return the $comment
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     *
     * @return the $expireDate
     */
    public function getExpireDate()
    {
        return $this->expireDate;
    }

    /**
     *
     * @return the $showPromo
     */
    public function getShowPromo()
    {
        return $this->showPromo;
    }

    /**
     *
     * @return the $quotePromo
     */
    public function getQuotePromo()
    {
        return $this->quotePromo;
    }

    /**
     *
     * @return the $quoteExclusive
     */
    public function getQuoteExclusive()
    {
        return $this->quoteExclusive;
    }

    /**
     *
     * @param field_type $comment            
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     *
     * @param field_type $expireDate            
     */
    public function setExpireDate($expireDate)
    {
        $this->expireDate = $expireDate;
    }

    /**
     *
     * @param boolean $showPromo            
     */
    public function setShowPromo($showPromo)
    {
        $this->showPromo = $showPromo;
    }

    /**
     *
     * @param number $quotePromo            
     */
    public function setQuotePromo($quotePromo)
    {
        $this->quotePromo = $quotePromo;
    }

    /**
     *
     * @param number $quoteExclusive            
     */
    public function setQuoteExclusive($quoteExclusive)
    {
        $this->quoteExclusive = $quoteExclusive;
    }

    public function subQuote($type)
    {
        if ($type == 'Promo') {
            $this->quotePromo = ($this->quotePromo > 1) ? $this->quotePromo - 1 : 0;
        } else {
            $this->quoteExclusive = ($this->quoteExclusive > 1) ? $this->quoteExclusive - 1 : 0;
        }
    }

    /**
     *
     * @return the $PaymentPage
     */
    public function getPaymentPage()
    {
        return $this->PaymentPage;
    }

    /**
     *
     * @param field_type $PaymentPage            
     */
    public function setPaymentPage($PaymentPage)
    {
        $this->PaymentPage = $PaymentPage;
    }

    /**
     *
     * @return the $active
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     *
     * @param boolean $active            
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    public function __toString()
    {
        return sprintf('%s', $this->getEmail());
    }
}