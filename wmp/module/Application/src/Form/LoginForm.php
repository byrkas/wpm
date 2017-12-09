<?php
namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element;
use Zend\InputFilter\InputFilterProviderInterface;
use Doctrine\Common\Persistence\ObjectManager;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

class LoginForm extends Form implements InputFilterProviderInterface
{
    protected $objectManager;
    
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
        parent::__construct('form');  
                
        $this->setAttributes([
            'method' => 'post',
            'class' => 'form-horizontal'
        ]);        
        
        $this->add([
            'name' => 'redirect_url',
            'type'  => Element\Hidden::class,
        ])->add([
            'name' => 'email',
            'options' => [
                'label' => 'Email',
            ],
            'attributes' => [
            ],
            'type'  => Element\Email::class,
        ])->add([
            'name' => 'password',
            'options' => [
                'label' => 'Password',
            ],
            'attributes' => [
            ],
            'type'  => Element\Password::class,
        ])->add([
            'name' => 'remember_me',
            'options' => [
                'label' => 'Remember me',
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
        ]);
    }

    public function getInputFilterSpecification()
    {
        return [
            'email' => [
                'required' => true,
                'validators'    =>  [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                    [
                        'name'	=> 'DoctrineModule\Validator\ObjectExists',
                        'options' => [
                            'object_repository' => $this->objectManager->getRepository('Application\Entity\Admin'),
                            'fields' => 'email'
                        ],
                    ],
                ],
                'filters' => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
            ],
            'password' => [
                'required' => true,
                'validators'    =>  [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
                'filters' => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
            ]
        ];
    }
}