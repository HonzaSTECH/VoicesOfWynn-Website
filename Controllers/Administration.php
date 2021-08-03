<?php

namespace VoicesOfWynn\Controllers;

class Administration extends Controller
{
    
    /**
     * @inheritDoc
     */
    public function process(array $args): bool
    {
        if (!isset($_SESSION['user']) || !$_SESSION['user']->isSysAdmin()) {
            //No user is logged in or the logged user is not system admin
            $errorController = new Error403();
            return $errorController->process(array());
        }
        
        self::$data['base_title'] = 'Administration';
        self::$data['base_description'] = 'Tool for the administrators to manage accounts of the contributors.';
        self::$data['base_keywords'] = 'Minecraft,Wynncraft,Mod,Voice,Administration,Management';
    
        self::$views[] = 'administration';
        return true;
    }
}