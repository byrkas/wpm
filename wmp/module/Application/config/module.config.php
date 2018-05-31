<?php
namespace Application;

use Zend\Router\Http\Literal;
use Zend\Router\Http\Hostname;
use Zend\Router\Http\Segment;
use Zend\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'backend' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller' => Controller\BackendController::class,
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '[:action[/:id]][/]',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller' => Controller\BackendController::class,
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'user' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => 'user[-:effect[/:id]][/]',
                            'constraints' => [
                                'effect'     => 'list|edit|delete',
                            ],
                            'defaults' => [
                                'controller' => Controller\BackendController::class,
                                'action'     => 'user',
                                'effect'    =>  'list'
                            ],
                        ],
                    ],
                    'page' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => 'page[-:effect[/:id]][/]',
                            'constraints' => [
                                'effect'     => 'list|edit|delete',
                            ],
                            'defaults' => [
                                'controller' => Controller\BackendController::class,
                                'action'     => 'page',
                                'effect'    =>  'list'
                            ],
                        ],
                    ],
                    'payment_page' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => 'payment-page[-:effect[/:id]][/]',
                            'constraints' => [
                                'effect'     => 'list|edit|delete',
                            ],
                            'defaults' => [
                                'controller' => Controller\BackendController::class,
                                'action'     => 'payment-page',
                                'effect'    =>  'list'
                            ],
                        ],
                    ],
                    'genre' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => 'genre[-:effect[/:id]][/]',
                            'constraints' => [
                                'effect'     => 'list|edit|delete',
                            ],
                            'defaults' => [
                                'controller' => Controller\BackendController::class,
                                'action'     => 'genre',
                                'effect'    =>  'list'
                            ],
                        ],
                    ],
                    'tracks' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => 'tracks[-:effect[/:id]][/]',
                            'constraints' => [
                                'effect'     => 'list|edit|delete',
                            ],
                            'defaults' => [
                                'controller' => Controller\BackendController::class,
                                'action'     => 'tracks',
                                'effect'    =>  'list'
                            ],
                        ],
                    ],
                    'downloads' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => 'downloads[/:id][/]',
                            'constraints' => [
                            ],
                            'defaults' => [
                                'controller' => Controller\BackendController::class,
                                'action'     => 'downloads',
                            ],
                        ],
                    ],
                    'ban_ip' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => 'ban[-:effect[/:id]][/]',
                            'constraints' => [
                                'effect'     => 'list|edit|delete',
                            ],
                            'defaults' => [
                                'controller' => Controller\BackendController::class,
                                'action'     => 'ban',
                                'effect'    =>  'list'
                            ],
                        ],
                    ],
                    'setting' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => 'setting-:code[/]',
                            'constraints' => [
                            ],
                            'defaults' => [
                                'controller' => Controller\BackendController::class,
                                'action'     => 'setting',
                            ],
                        ],
                    ],
                    'login' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => 'login[/]',
                            'defaults' => [
                                'controller' => Controller\BackendController::class,
                                'action'     => 'login',
                            ],
                        ],
                    ],
                    'logout' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => 'logout[/]',
                            'defaults' => [
                                'controller' => Controller\BackendController::class,
                                'action'     => 'logout',
                            ],
                        ],
                    ],
                ]
            ],
            'api' => [
                'type' => Hostname::class,
                'options' => [
                    'route'    => 'api.djdownload.me',
                    'defaults' => [
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/[:action[/:id]][/]',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'tracks' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/tracks[/:id][/]',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action'     => 'tracks',
                            ],
                        ],
                    ],
                    'search_results' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/search-results[/:query][/]',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action'     => 'search-results',
                            ],
                        ],
                    ],
                    'page' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/page/:slug[/]',
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action'     => 'page',
                            ],
                        ],
                    ],
                    'robots' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/robots[/]',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action'     => 'robots',
                            ],
                        ],
                    ],
                ]
            ]
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => Controller\Factory\IndexControllerFactory::class,
            Controller\BackendController::class => Controller\Factory\BackendControllerFactory::class,
        ],
    ],
    'view_helpers' => [ 
    	'invokables' => [
    		'showMessages'         => View\Helper\ShowMessages::class,
    	],
    ],
    'view_manager' => [
        'display_not_found_reason' => false,
        'display_exceptions'       => false,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
	    'strategies' => [
	        'ViewJsonStrategy'
	    ]
    ],
    'access_filter' => [
        'controllers' => [
            Controller\BackendController::class => [
                ['actions' => ['login', 'logout','publishTrack'], 'allow' => '*'],
                ['actions' => ['index','import','genre','setting','ban','page','user','tracks'], 'allow' => '@']
            ],
            Controller\IndexController::class => [
                ['actions' => ['index','login','logout','maintain'], 'allow' => '*'],
            ],
        ]
    ],
    'service_manager' => [
        'factories' => [
            \Zend\Authentication\AuthenticationService::class => Service\Factory\AuthenticationServiceFactory::class,
            Service\AuthAdapter::class => Service\Factory\AuthAdapterFactory::class,
            Service\AuthManager::class => Service\Factory\AuthManagerFactory::class,
            Service\UserManager::class => Service\Factory\UserManagerFactory::class,
            'navigation' => Zend\Navigation\Service\DefaultNavigationFactory::class,
            Service\ImportManager::class => Service\Factory\ImportManagerFactory::class,
        ],
    ],
    'navigation'    =>  [
        'default'   =>  [
            [
                'label' => 'Tracks',
                'route' => 'home',
            ],
            [
                'label' => 'TOP 100',
                'route' => 'home',
            ],
        ],
    ]
];
