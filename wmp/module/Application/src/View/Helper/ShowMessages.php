<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\View\Helper\FlashMessenger;

class ShowMessages extends AbstractHelper
{
    public function __invoke()
    {
        $messenger = new FlashMessenger();
        $error_messages = $messenger->getErrorMessages();
        $success_messages = $messenger->getSuccessMessages();
        $messages = $messenger->getMessages();
        $result = '';
        if (count($error_messages)) {
            $result .= '<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Warning: ';
            $result .= implode(' ',$error_messages);
            $result .= '<button class="close" type="button" data-dismiss="alert">×</button></div>';
        }
        if (count($success_messages)) {
            $result .= '<div class="alert alert-success"><i class="fa fa-check-circle"></i> Success: ';
            $result .= implode(' ',$success_messages);
            $result .= '<button class="close" type="button" data-dismiss="alert">×</button></div>';
        }

        if (count($messages)) {
            $result .= '<div class="alert alert-info">';
            foreach ($messages as $message) {
                $result .= '<p>' . $message . '</p>';
            }
            $result .= '<button class="close" type="button" data-dismiss="alert">×</button></div>';
        }

        return $result;
    }
}