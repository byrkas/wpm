<?php
namespace Application\Controller\Factory;

use Interop\Container\ContainerInterface;
use User\Controller\AuthController;
use Zend\ServiceManager\Factory\FactoryInterface;
use Application\Service\AuthManager;
use Application\Service\UserManager;
use Application\Service\ImportManager;

class BackendControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $entityManager = $container->get('doctrine.entitymanager.orm_default');
        $authManager = $container->get(AuthManager::class);
        $authService = $container->get(\Zend\Authentication\AuthenticationService::class);
        $userManager = $container->get(UserManager::class);
        $importManager = $container->get(ImportManager::class);
        return new \Application\Controller\BackendController($entityManager, $config, $authManager, $authService, $userManager, $importManager);
    }
}