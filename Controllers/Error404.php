<?php


namespace VoicesOfWynn\Controllers;


class Error404 extends Controller
{
    
    /**
     * Method setting the view and headers for the error page
     * @param array $args Leave this array empty - no data are used
     * @return bool TRUE, if the data was set successfully
     */
    public function process(array $args): bool
    {
        header("HTTP/1.1 404 Not Found");
        
        self::$data['base_title'] = 'Page not found';
        self::$data['base_description'] = 'Oops, you probably didn\'t want to end up here.';
        self::$data['base_keywords'] = '';
        
        self::$views[] = 'error404';
        return true;
    }
}

