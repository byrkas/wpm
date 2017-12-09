<?php
namespace Application\Service\Factory;
use Interop\Container\ContainerInterface;
use Application\Service\ImportManager;
/**
 * This is the factory class for UserManager service. The purpose of the factory
 * is to instantiate the service and pass it dependencies (inject dependencies).
 */
class ImportManagerFactory
{
    /**
     * This method creates the UserManager service and returns its instance.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $entityManager = $container->get('doctrine.entitymanager.orm_default');
        $config = $container->get('Config');

        return new ImportManager($config, $entityManager);
    }
}