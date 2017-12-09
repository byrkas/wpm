<?php
namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element;
use Zend\InputFilter\InputFilterProviderInterface;
use Doctrine\Common\Persistence\ObjectManager;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Application\Entity\Page;

class PageForm extends Form implements InputFilterProviderInterface
{
    protected $create = true;
    protected $objectManager;
    
    public function __construct(ObjectManager $objectManager, $create = true)
    {
        parent::__construct('form');  
        $this->create = $create;
        $this->objectManager  = $objectManager;
        $this->setHydrator(new DoctrineHydrator($objectManager, 'Application\Entity\Page'))->setObject(new Page());   
        
        $this->setAttributes([
            'method' => 'post',
            'class' => 'form-horizontal',
            'enctype'   =>  'multipart/form-data',
        ]);        
        
        $this->add([
            'name' => 'id',
            'type'  => Element\Hidden::class,
        ])->add([
            'name' => 'title',
            'options' => [
                'label' => 'Title',
                'label_attributes' => [
                    'class' => 'col-sm-2 required'
                ],
                'column-size' => 'sm-10',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
            ],
            'type'  => Element\Text::class,
        ])->add([
            'name' => 'content',
            'options' => [
                'label' => 'Content',
                'label_attributes' => [
                    'class' => 'col-sm-2'
                ],
                'column-size' => 'sm-10',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
                'class' =>  'summernote'
            ],
            'type'  => Element\Textarea::class,
        ])->add([
            'name' => 'isPublished',
            'options' => [
                'label' => 'Is Published',
                'column-size' => 'sm-8 col-sm-offset-2',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
            ],
            'type'  => Element\Checkbox::class,
        ])->add([
            'name' => 'isPayment',
            'options' => [
                'label' => 'Is Payment',
                'column-size' => 'sm-8 col-sm-offset-2',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
            ],
            'type'  => Element\Checkbox::class,
        ])->add([
            'name' => 'isPaymentDefault',
            'options' => [
                'label' => 'Is Payment Default',
                'column-size' => 'sm-8 col-sm-offset-2',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
            ],
            'type'  => Element\Checkbox::class,
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
        return [
            'title' => [
                'required' => true,
                'filters' => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
            ],
            'content' => [
                'required' => false,
            ],
            'id' => [
                'required' => false,
            ],
            'isPublished' => [
                'required' => false,
            ],
            'isPayment' => [
                'required' => false,
            ],
            'isPaymentDefault' => [
                'required' => false,
            ]
        ];
    }
}