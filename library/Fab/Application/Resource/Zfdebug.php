<?php

class Fab_Application_Resource_Zfdebug extends Zend_Application_Resource_ResourceAbstract
{
    public function init()
    {
        $bootstrap = $this->getBootstrap();
        $options = $this->getOptions();
        
        if (array_key_exists('plugins', $options)) {
            // Use plugins array directly
            $plugins = $options['plugins'];
        } else {
            // Configure default plugins
            $plugins = array('Variables');
            
            // Add Doctrine plugin
            if ($bootstrap->hasResource('doctrine')) {
                $plugins[] = 'Fab_Controller_Plugin_Debug_Plugin_Doctrine';
            }
            
            // Add File plugin
            $plugins['File'] = array('base_path' => APPLICATION_PATH . '/..', 'library' => array('ZendX', 'Doctrine', 'ZFDoctrine', 'Fab'));
            
            // Add Cache plugin
            if ($bootstrap->hasResource('cachemanager')) {
                $bootstrap->bootstrap('cachemanager');
                $cacheManager = $bootstrap->getResource('cachemanager');
                $cacheBackends = array();
                foreach ($cacheManager->getCaches() as $cacheName => $cacheFrontend) {
                    $cacheBackends[$cacheName] = $cacheFrontend->getBackend();
                }
                $plugins['Cache'] = array('backend' => $cacheBackends);
            }
            
            // Add Exception plugin
            $plugins[] = 'Exception';
        }

        $zfDebug = new ZFDebug_Controller_Plugin_Debug(array('plugins' => $plugins));
        
        $front = $bootstrap->bootstrap('frontcontroller');
        $front = $bootstrap->getResource('frontcontroller');
        $front->registerPlugin($zfDebug);

        return $zfDebug;
    }
}
