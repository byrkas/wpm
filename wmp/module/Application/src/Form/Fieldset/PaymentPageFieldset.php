<?php
namespace Application\Form\Fieldset;

use Zend\Form\Fieldset;
use Zend\Form\Element;
use Zend\InputFilter\InputFilterProviderInterface;
use Doctrine\Common\Persistence\ObjectManager;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Application\Entity\Page;

class PaymentPageFieldset extends Fieldset implements InputFilterProviderInterface
{
    public function __construct(ObjectManager $objectManager)
    {        
        parent::__construct('PaymentPage');
        $this->setHydrator(new DoctrineHydrator($objectManager, 'Application\Entity\Page'))->setObject(new Page());
        
        $this->setOptions(['label' => 'Payment Page']);
        
        $this->add([
            'type'  => Element\Hidden::class,
            'name' => 'id',
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
                'value' =>  'Payment page'
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
            ],
            'type'  => Element\Textarea::class,
        ]);
    }
    
    public function getInputFilterSpecification()
    {
        return [
            'id'   =>  [
                'required'  =>  false
            ],
            'title'   =>  [
                'required'  =>  true
            ],
            'content'   =>  [
                'required'  =>  false
            ], 
        ];
    }
}