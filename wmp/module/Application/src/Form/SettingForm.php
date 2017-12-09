<?php
namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element;
use Zend\InputFilter\InputFilterProviderInterface;
use Doctrine\Common\Persistence\ObjectManager;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Application\Entity\Setting;

class SettingForm extends Form implements InputFilterProviderInterface
{
    protected $objectManager;
    
    public function __construct(ObjectManager $objectManager, $code)
    {
        parent::__construct('form');  
        $this->objectManager  = $objectManager;
        $this->setHydrator(new DoctrineHydrator($objectManager, 'Application\Entity\Setting'))->setObject(new Setting());   
        
        $this->setAttributes([
            'method' => 'post',
            'class' => 'form-horizontal',
            'enctype'   =>  'multipart/form-data',
        ]);        
        
        $this->add([
            'name' => 'id',
            'type'  => Element\Hidden::class,
        ])->add([
            'name' => 'code',
            'options' => [
                'label' => 'Code',
                'label_attributes' => [
                    'class' => 'col-sm-2'
                ],
                'column-size' => 'sm-10',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
                'readonly'  =>  true
            ],
            'type'  => Element\Text::class,
        ]);
        if($code == 'footer')
            $this->add([
                'name' => 'value',
                'options' => [
                    'label' => 'Footer content',
                    'label_attributes' => [
                        'class' => 'col-sm-2 required'
                    ],
                    'column-size' => 'sm-10',
                    'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
                ],
                'attributes' => [
                'class' =>  'summernote'
                ],
                'type'  => Element\Textarea::class,
            ]);            
        elseif($code != 'maintain_mode' && $code != 'site_mode')
            $this->add([
                'name' => 'value',
                'options' => [
                    'label' => 'Template',
                    'label_attributes' => [
                        'class' => 'col-sm-2 required'
                    ],
                    'column-size' => 'sm-10',
                    'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
                ],
                'attributes' => [
                ],
                'type'  => Element\Text::class,
            ]);
        else
            $this->add([
            'name' => 'value',
            'options' => [
                'column-size' => 'sm-8',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
                'data-toggle'   =>  'toggle',
            ],
            'type'  => Element\Checkbox::class,
        ]);
        
        $this->add([
            'name' => 'submit',
            'options' => [
            ],
            'attributes' => ['value' => 'Submit'],
            'type'  => Element\Submit::class
        ]);
    }

    public function getInputFilterSpecification()
    {
        
        return [
            'value' => [
                'required' => false,
            ],
            'id' => [
                'required' => false,
            ],
            'code' => [
                'required' => false,
            ]
        ];
    }
}