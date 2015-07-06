<?php

namespace Vci;

class Module 
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'prefixes' => array(
                    __NAMESPACE__ => __DIR__ . '/library/' . __NAMESPACE__,
                ),
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/library/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig($env = null)
    {
        return array();
    }
}
