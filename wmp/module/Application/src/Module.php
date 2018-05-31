<?php
namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Controller\AbstractActionController;
use Application\Controller\IndexController;
use Application\Controller\BackendController;
use Application\Service\User;
use Application\Service\AuthManager;
use Zend\Session\SessionManager;

class Module
{
    const VERSION = '3.0.3-dev';

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        $application = $event->getApplication();
        $serviceManager = $application->getServiceManager();
        
        $eventManager        = $event->getApplication()->getEventManager();    
        $sharedEventManager = $eventManager->getSharedManager();
        $sharedEventManager->attach(AbstractActionController::class, MvcEvent::EVENT_DISPATCH, [$this, 'onDispatch'], 100); 
        
        $eventManager->attach('dispatch.error', function($event){
            $logger = new \Zend\Log\Logger;
            $writer = new \Zend\Log\Writer\Stream('data/exception.log');
            $logger->addWriter($writer);
            
            // Log PHP errors
            \Zend\Log\Logger::registerErrorHandler($logger);
            // Log exceptions
            \Zend\Log\Logger::registerExceptionHandler($logger);
            
            $exception = $event->getResult()->exception;
            if ($exception) {
                $request = $event->getApplication()->getRequest();
                $response = $event->getApplication()->getResponse();
                
                $logger->info([
                    'status' => $response->getStatusCode(),
                    'method' => $request->getMethod(),
                    'uri'    => (string) $request->getUri(),
                    'exception'  => $exception->getMessage(),
                ]);
            }
        });
        
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $sessionManager = $serviceManager->get(SessionManager::class);
        $this->forgetInvalidSession($sessionManager);
    }    
    
    protected function forgetInvalidSession($sessionManager) {
        try {
            $sessionManager->start();
            return;
        } catch (\Exception $e) {
        }
        /**
         * Session validation failed: toast it and carry on.
         */
        // @codeCoverageIgnoreStart
        session_unset();
        // @codeCoverageIgnoreEnd
    }
    
    public function onDispatch(MvcEvent $event)
    {
        // Get controller and action to which the HTTP request was dispatched.
        $controller = $event->getTarget();
        $controllerName = $event->getRouteMatch()->getParam('controller', null);
        $actionName = $event->getRouteMatch()->getParam('action', null);
        
        /* echo $actionName.' '.$controllerName;
        die('asd'); */
        
        if($controllerName ==  BackendController::class){
            $controller->layout('layout/backend-layout');
            // Convert dash-style action name to camel-case.
            $actionName = str_replace('-', '', lcfirst(ucwords($actionName, '-')));
            
            // Get the instance of AuthManager service.
            $authManager = $event->getApplication()->getServiceManager()->get(AuthManager::class);
            
            // Execute the access filter on every controller except AuthController
            // (to avoid infinite redirect).
            if (!$authManager->filterAccess($controllerName, $actionName)) {
                // Remember the URL of the page the user tried to access. We will
                // redirect the user to that URL after successful login.
                $uri = $event->getApplication()->getRequest()->getUri();
                // Make the URL relative (remove scheme, user info, host name and port)
                // to avoid redirecting to other domain by a malicious user.
                $uri->setScheme(null)
                ->setHost(null)
                ->setPort(null)
                ->setUserInfo(null);
                $redirectUrl = $uri->toString();
            
                if($controllerName == BackendController::class){
                    // Redirect the user to the "Login" page.
                    return $controller->redirect()->toRoute('backend/login', [],
                        ['query'=>['redirectUrl'=>$redirectUrl]]);
                }/* else{
                return $controller->redirect()->toRoute('api/login', [],
                ['query'=>['redirectUrl'=>$redirectUrl]]);
            
                } */
            }
        }
        /* if($controllerName == IndexController::class && $actionName != 'maintain'){
            $entityManager = $event->getApplication()->getServiceManager()->get('doctrine.entitymanager.orm_default');
            $checkMaintainMode = $entityManager->getRepository('Application\Entity\Setting')->checkMaintainMode();
            if($checkMaintainMode){
                return $controller->redirect()->toRoute('api/maintain');
            }
        } */
       
    }
}
