<?php
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Zend\Session\Storage\SessionArrayStorage;
use Zend\Session\Validator\RemoteAddr;
use Zend\Session\Validator\HttpUserAgent;

return [
    // Session configuration.
    'session_config' => [
        // Session cookie will expire in 5 hour.
        'cookie_lifetime' => 60*60*5,
        // Session data will be stored on server maximum for 30 days.
        'gc_maxlifetime'     => 60*60*24*30,
    ],
    // Session manager configuration.
    'session_manager' => [
        // Session validators (used for security).
        'validators' => [
            RemoteAddr::class,
           // HttpUserAgent::class,
        ]
    ],
    // Session storage configuration.
    'session_storage' => [
        'type' => SessionArrayStorage::class
    ],
    'languages' =>  [
        'default'   =>  [
            'en'    =>  [
                'name'  =>  'English',
                'locale'    =>  'en_GB',
            ]
        ],
    ],
	'doctrine' => [
		'connection' => [
            'orm_default' => [
                'driverClass' => Driver::class,               
                'doctrine_type_mappings' => [
                    'enum' => 'string'
                ],   
            ],
        ], 
        'migrations_configuration' => [
            'orm_default' => [
                'directory' => 'data/Migrations',
                'name'      => 'Doctrine Database Migrations',
                'namespace' => 'Migrations',
                'table'     => 'migrations',
            ],
        ],
        'configuration'	=>	[
            'orm_default'	=> [
                'datetime_functions' => [
                    'date'          => 'Application\Entity\Func\Date',
                ],
                'numeric_functions' => [
                    'age'           => 'Application\Entity\Func\Age',
                    'dayname'       => 'Application\Entity\Func\DayName',
                    'day'           => 'Application\Entity\Func\Day',
                    'month'         => 'Application\Entity\Func\Month',
                    'year'          => 'Application\Entity\Func\Year',
                ],
                'string_functions'  => [
                    'group_concat'  => 'Application\Entity\Func\GroupConcat',
                    'field'         => 'Application\Entity\Func\Field',
                    'ifelse'        => 'Application\Entity\Func\IfElse',
                    'ifnull'        => 'Application\Entity\Func\IfNull',
                    'nullif'        => 'Application\Entity\Func\NullIf',
                    'replace'       => 'Application\Entity\Func\Replace',
                ]       
            ]
        ],
	    'cache' => [
            'my_memcache' => [
                'instance' => 'doctrine.cache.my_memcache',
            ],
        ],
        'driver' => [
            'configuration' => [
                'orm_default' => [
                    'metadata_cache' => 'array',
                    'query_cache' => 'array',
                    'result_cache' => 'array',
                    'hydration_cache' => 'array',
                    'generate_proxies' => true,
                    'proxy_dir' => 'data/DoctrineORMModule/Proxy',
                    'proxy_namespace' => 'DoctrineORMModule\Proxy',
                ],
            ],
            'db_entities' => [
                'class' => AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../../module/Application/src/Entity',]
            ],
            'loggable_metadata_driver'  =>  [
                'class' =>  'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' =>  'array',
                'paths'    =>  [
                    'vendor/gedmo/doctrine-extensions/lib/Gedmo/Loggable/Entity'  ,
                ],
            ],
            'orm_default' => [
                'drivers' => [
                    'Application\Entity' => 'db_entities',
                    'Gedmo\Loggable\Entity' =>  'loggable_metadata_driver',
                ]
            ]
        ],
        'eventmanager'  =>  [
            'orm_default'   =>  [
                'subscribers'   =>  [
                    'Gedmo\Timestampable\TimestampableListener',
                    'Gedmo\Sluggable\SluggableListener',
                ]
            ]
        ]
	],
];
