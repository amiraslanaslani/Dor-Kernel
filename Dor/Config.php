<?php
/**
 * User: Amir Aslan Aslani
 * Date: 6/29/19
 * Time: 11:56 PM
 */

namespace Dor;

class Config
{
    private $content = [];
    private $root;

    public function __construct($file, $root_address = __DIR__){
        $this->content = include($file);
        $this->root = $root_address;
    }

    public function isOnDebugMode():bool{
        return $this->getContent()['debug_mode'] ? true : false;
    }


    // Getters

    public function getRootAddress(){
        return $this->root;
    }

    public function getContent(){
        return $this->content;
    }

    public function getDatabaseConfig(){
        return $this->getContent()['database'];
    }


    // Directories

    public function getViewDirectory(){
        return isset($this->getContent()['system']['directories']['view']) ?
            $this->getRootAddress() . $this->getContent()['system']['directories']['view'] . '/' :
            null;
    }

    public function getConfigsDirectory(){
        return isset($this->getContent()['system']['directories']['configs']) ? 
            $this->getRootAddress() . $this->getContent()['system']['directories']['configs'] . '/' :
            null;
    }

    public function getModelDirectory(){
        return isset($this->getContent()['system']['directories']['model']) ? 
            $this->getRootAddress() . $this->getContent()['system']['directories']['model'] . '/' :
            null;
    }

    public function getControllerDirectory(){
        return isset($this->getContent()['system']['directories']['controller']) ? 
            $this->getRootAddress() . $this->getContent()['system']['directories']['controller'] . '/' :
            null;
    }

    public function getRoutesDirectory(){
        return isset($this->getContent()['system']['directories']['routes']) ? 
            $this->getRootAddress() . $this->getContent()['system']['directories']['routes'] . '/' :
            null;
    }
}