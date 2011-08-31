<?php

class Fab_Application_Resource_Cron extends Zend_Application_Resource_ResourceAbstract
{
    public function init()
    {
        $options = $this->getOptions();

        if (array_key_exists('pluginPaths', $options)) {
            $cron = new Fab_Cron($options['pluginPaths']);
        } else {
            $cron = new Fab_Cron(array(
                'Application_Cron' => realpath(APPLICATION_PATH . '/crons/'),
            ));
        }

        if (array_key_exists('actions', $options)) {
            foreach ($options['actions'] as $name => $args) {
                $cron->addAction($name, $args);
            }
        }

        return $cron;
    }
}
