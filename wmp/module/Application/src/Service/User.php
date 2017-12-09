<?php  
namespace Application\Service;

use Zend\Session\SessionManager;
use Zend\Session\Container;
use Zend\View\Helper\FlashMessenger;
use Zend\Crypt\Password\Bcrypt;

class User
{   
    protected $authService;    
    protected $objectManager;    
    protected $flashMessenger;
    
    public function __construct($authService,$em)
    {
        $this->authService = $authService;     
        $this->objectManager = $em;       
        $this->flashMessenger = new FlashMessenger();
    }
        
    public function signin($email, $password, $rememberMe = null){
        $authAdapter = $this->authService->getAdapter();
        
        $authAdapter->setIdentityValue($email);
        $authAdapter->setCredentialValue($password);
        $authResult = $this->authService->authenticate();
        if ($authResult->isValid()) {
            $identity = $authResult->getIdentity();
            $this->authService->getStorage()->write($identity); 
            $this->objectManager->flush();
            
        }else{
            $messages = implode('\n',$authResult->getMessages());            
            $this->flashMessenger->addErrorMessage($messages);
        }
        
        return $authResult;
    }    
        
    public function signout()
    {        
        if ($this->authService->hasIdentity()) {
            $identity = $this->authService->getIdentity();        
            $this->authService->clearIdentity();
            $session = new Container('redirect');
            $session->__unset('redirect');
        }
        
        $sessionManager = new SessionManager();
        $sessionManager->forgetMe();
        
        return true;
    }
}