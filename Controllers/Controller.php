<?php


namespace VoicesOfWynn\Controllers;


abstract class Controller
{
    
    /**
     * @var $data array Data obtained by all controllers in the process
     */
    protected static array $data = array();
    
    /**
     * @var $views array List of views to use, from the most outer one to the most inner one
     */
    protected static array $views = array('base');
    
    /**
     * @var $cssFiles array List of CSS files to include into the final webpage; all CSS files must be in the 'css' folder
     */
    protected static array $cssFiles = array('classless');
    
    /**
     * @var $jsFiles array List of JS files to include into the final webpage; all JS files must be in the 'js' folder
     */
    protected static array $jsFiles = array('jquery');
    
    /**
     * Public method processing passed data, specific for each controller
     * @param array $args Arguments to process
     * @return bool TRUE, if everything worked as expected, FALSE otherwise
     */
    public abstract function process(array $args): bool;
}

