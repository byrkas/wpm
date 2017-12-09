<?php
namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element;
use Zend\InputFilter\InputFilterProviderInterface;
use Doctrine\Common\Persistence\ObjectManager;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Application\Entity\User;

class UserForm extends Form implements InputFilterProviderInterface
{
    protected $create = true;
    protected $objectManager;
    
    public function __construct(ObjectManager $objectManager, $create = true)
    {
        parent::__construct('form');  
        $this->create = $create;
        $this->objectManager  = $objectManager;
        $this->setHydrator(new DoctrineHydrator($objectManager, 'Application\Entity\User'))->setObject(new User());   
        //$pageFieldset = new Fieldset\PaymentPageFieldset($objectManager);
        $pages = $objectManager->getRepository('Application\Entity\Page')->getPaymentPagesList();
                
        $this->setAttributes([ 
            'method' => 'post',
            'class' => 'form-horizontal',
            'enctype'   =>  'multipart/form-data',
        ]);        
        
        $this->add([
            'name' => 'id',
            'type'  => Element\Hidden::class,
        ])->add([
            'name' => 'email',
            'options' => [
                'label' => 'Email',
                'label_attributes' => [
                    'class' => 'col-sm-2 required'
                ],
                'column-size' => 'sm-10',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
            ],
            'type'  => Element\Email::class,
        ])->add([
            'name' => 'password',
            'options' => [
                'label' => 'Password',
                'label_attributes' => [
                    'class' => 'col-sm-2 '.(($this->create)?'required':'')
                ],
                'column-size' => 'sm-10',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
            ],
            'type'  => Element\Text::class,
        ])->add([
            'name' => 'quotePromo',
            'options' => [
                'label' => 'Quote Promo',
                'label_attributes' => [
                    'class' => 'col-sm-6'
                ],
                'column-size' => 'sm-4',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
            ],
            'type'  => Element\Number::class,
        ])->add([
            'name' => 'quoteExclusive',
            'options' => [
                'label' => 'Quote Exclusive',
                'label_attributes' => [
                    'class' => 'col-sm-6'
                ],
                'column-size' => 'sm-4',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
            ],
            'type'  => Element\Number::class,
        ])->add([
            'name' => 'expireDate',
            'options' => [
                'label' => 'Expire Date',
                'format' => 'Y-m-d H:i',
                'column-size' => 'sm-8',
                'label_attributes' => [
                    'class' => 'col-sm-4 required'
                ],
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
                'id'    =>  'dateBegin',
                'class'    =>  'date_timepicker',
            ],
            'type'  => Element\DateTime::class,
        ])->add([
            'name' => 'comment',
            'options' => [
                'label' => 'Comment',
                'label_attributes' => [
                    'class' => 'col-sm-2'
                ],
                'column-size' => 'sm-10',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
            ],
            'type'  => Element\Textarea::class,
        ])->add([
            'name' => 'showPromo',
            'options' => [
                'label' => 'Show Promo',
                'column-size' => 'sm-8 col-sm-offset-2',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
            ],
            'type'  => Element\Checkbox::class,
        ])->add([
            'name' => 'active',
            'options' => [
                'label' => 'Is Active',
                'column-size' => 'sm-8 col-sm-offset-2',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
            ],
            'type'  => Element\Checkbox::class,
        ])//->add($pageFieldset)
        ->add([
            'name'  =>  'PaymentPage',
            'options'   =>  [
                'label' =>  'Payment page',
                'label_attributes' => [
                    'class' => 'col-sm-2'
                ],
                'column-size' => 'sm-10',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL,
                'empty_option' => 'Please choose your payment page',
                'object_manager' => $this->objectManager,
                'target_class'   => 'Application\Entity\Page',
                'is_method'      => true,
                'property'       => 'title',
                'find_method'    => [
                    'name'   => 'findBy',
                    'params' => [
                        'criteria' => ['isPayment' => 1],
                    ],
                ],
                //'value_options' => $pages,
            ],
            'attributes'    =>  [   
            ],
            'type'  =>  'DoctrineModule\Form\Element\ObjectSelect'
        ])
        ->add([
            'name' => 'submit',
            'options' => [
            ],
            'attributes' => ['value' => 'Submit'],
            'type'  => Element\Submit::class
        ])->add([
            'name' => 'cancel',
            'options' => [
            ],
            'attributes' => ['value' => 'Cancel'],
            'type'  => Element\Submit::class
        ]);
    }

    public function getInputFilterSpecification()
    {
        $emailValidators = [
            [
                'name' => 'StringLength',
                'options' => [
                    'encoding' => 'UTF-8',
                    'min' => 1,
                    'max' => 100,
                ],
            ],
        ];
        if($this->create){
            $emailValidators[] = [
                'name'	=> 'DoctrineModule\Validator\NoObjectExists',
                'options' => [
                    'object_repository' => $this->objectManager->getRepository('Application\Entity\User'),
                    'fields' => 'email'
                ],
            ];
        }
        return [
            'email' => [
                'required' => true,
                'filters' => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
            ],
            'password' => [
                'required' => ($this->create)?true:false,
            ],
            'id' => [
                'required' => false,
            ],
            'showPromo' => [
                'required' => false,
            ],
            'active' => [
                'required' => false,
            ]
        ];
    }
}