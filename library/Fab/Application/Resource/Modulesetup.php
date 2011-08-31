<?php

class Fab_Application_Resource_Modulesetup extends Zend_Application_Resource_ResourceAbstract
{

    public function init()
    {
        $this->_getModuleSetup();
    }

    /**
     * Load the module's ini files
     *
     * @return void
     */
    protected function _getModuleSetup()
    {
        $bootstrap = $this->getBootstrap();

        if (!($bootstrap instanceof Zend_Application_Bootstrap_Bootstrap)) {
            throw new Zend_Application_Exception('Invalid bootstrap class');
        }

        $bootstrap->bootstrap('frontcontroller');
        $front = $bootstrap->getResource('frontcontroller');
        $modules = $front->getControllerDirectory();

        foreach (array_keys($modules) as $module) {
            $configPath = $front->getModuleDirectory($module) . DIRECTORY_SEPARATOR . 'configs';
            if (file_exists($configPath)) {
                $cfgdir = new DirectoryIterator($configPath);
                $appOptions = $this->getBootstrap()->getOptions();

                foreach ($cfgdir as $file) {
                    if ($file->isFile()) {
                        $filename = $file->getFilename();
                        $options = $this->_loadConfig($configPath . DIRECTORY_SEPARATOR . $filename);
                        if (($len = strpos($filename, '.')) !== false) {
                            $cfgtype = substr($filename, 0, $len);
                        } else {
                            $cfgtype = $filename;
                        }

                        if (strtolower($cfgtype) == 'module') {
                            if (array_key_exists($module, $appOptions)) {
                                if (is_array($appOptions[$module])) {
                                    $appOptions[$module] = array_merge($appOptions[$module], $options);
                                } else {
                                    $appOptions[$module] = $options;
                                }
                            } else {
                                $appOptions[$module] = $options;
                            }
                        } else {
                            $appOptions[$module]['resources'][$cfgtype] = $options;
                        }
                    }
                }
                $this->getBootstrap()->setOptions($appOptions);
            } else {
                continue;
            }
        }
    }

    /**
     * Load configuration file of options
     *
     * @param  string $file
     * @throws Zend_Application_Resource_Exception When invalid configuration file is provided
     * @return array
     */
    protected function _loadConfig($file)
    {
        $environment = $this->getBootstrap()->getEnvironment();
        $suffix      = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        switch ($suffix) {
            case 'ini':
                $config = new Zend_Config_Ini($file, $environment);
                break;

            case 'xml':
                $config = new Zend_Config_Xml($file, $environment);
                break;

            case 'json':
                $config = new Zend_Config_Json($file, $environment);
                break;

            case 'yaml':
            case 'yml':
                $config = new Zend_Config_Yaml($file, $environment);
                break;

            case 'php':
            case 'inc':
                $config = include $file;
                if (!is_array($config)) {
                    throw new Zend_Application_Resource_Exception('Invalid configuration file provided; PHP file does not return array value');
                }
                return $config;
                break;

            default:
                throw new Zend_Application_Resource_Exception('Invalid configuration file provided; unknown config type');
        }

        return $config->toArray();
    }

}
