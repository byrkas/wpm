<?php
namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element;
use Zend\InputFilter\InputFilterProviderInterface;
use Doctrine\Common\Persistence\ObjectManager;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Application\Entity\BanIp;

class BanIpForm extends Form implements InputFilterProviderInterface
{
    protected $create = true;
    protected $objectManager;
    
    public function __construct(ObjectManager $objectManager, $create = true)
    {
        parent::__construct('form-genre');  
        $this->create = $create;
        $this->objectManager  = $objectManager;
        $this->setHydrator(new DoctrineHydrator($objectManager, 'Application\Entity\BanIp'))->setObject(new BanIp());   
        
        $this->setAttributes([
            'method' => 'post',
            'class' => 'form-horizontal',
            'enctype'   =>  'multipart/form-data',
        ]);        
        
        $this->add([
            'name' => 'id',
            'type'  => Element\Hidden::class,
        ])->add([
            'name' => 'ip',
            'options' => [
                'label' => 'IP',
                'label_attributes' => [
                    'class' => 'col-sm-2 required'
                ],
                'column-size' => 'sm-10',
                'twb-layout' => \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            ],
            'attributes' => [
            ],
            'type'  => Element\Text::class,
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
        $titleValidators = [
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
            $titleValidators[] = [
                        'name'	=> 'DoctrineModule\Validator\NoObjectExists',
                        'options' => [
                            'object_repository' => $this->objectManager->getRepository('Application\Entity\BanIp'),
                            'fields' => 'ip'
                        ],
                    ];
        }
        return [
            'ip' => [
                'required' => true,
                'validators'    =>  $titleValidators,
                'filters' => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
            ],
            'id' => [
                'required' => false,
            ]
        ];
    }
}