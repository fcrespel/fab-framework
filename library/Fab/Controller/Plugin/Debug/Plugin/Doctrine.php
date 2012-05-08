<?php

/**
 * ZFDebug Doctrine ORM plugin created by Danceric
 * http://www.danceric.net/2009/06/06/zfdebug-and-doctrine-orm/
 */
 
class Fab_Controller_Plugin_Debug_Plugin_Doctrine extends ZFDebug_Controller_Plugin_Debug_Plugin implements ZFDebug_Controller_Plugin_Debug_Plugin_Interface
{
    /**
     * Contains plugin identifier name
     *
     * @var string
     */
    protected $_identifier = 'doctrine';
 
    /**
     * @var array Doctrine connection profiler that will listen to events
     */
    protected $_profilers = array();
 
    /**
     * Create ZFDebug_Controller_Plugin_Debug_Plugin_Variables
     *
     * @param Doctrine_Manager|array $options
     * @return void
     */
    public function __construct(array $options = array())
    {
        if(!isset($options['manager']) || !count($options['manager'])) {
            if (Doctrine_Manager::getInstance()) {
                $options['manager'] = Doctrine_Manager::getInstance();
            }
        }
 
        foreach ($options['manager']->getIterator() as $connection) {
            $this->_profilers[$connection->getName()] = new Doctrine_Connection_Profiler();
            $connection->setListener($this->_profilers[$connection->getName()]);
        }
    }
 
    /**
     * Gets identifier for this plugin
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }
    
    /**
     * Returns the base64 encoded icon
     *
     * @return string
     **/
    public function getIconData()
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAEYSURBVBgZBcHPio5hGAfg6/2+R980k6wmJgsJ5U/ZOAqbSc2GnXOwUg7BESgLUeIQ1GSjLFnMwsKGGg1qxJRmPM97/1zXFAAAAEADdlfZzr26miup2svnelq7d2aYgt3rebl585wN6+K3I1/9fJe7O/uIePP2SypJkiRJ0vMhr55FLCA3zgIAOK9uQ4MS361ZOSX+OrTvkgINSjS/HIvhjxNNFGgQsbSmabohKDNoUGLohsls6BaiQIMSs2FYmnXdUsygQYmumy3Nhi6igwalDEOJEjPKP7CA2aFNK8Bkyy3fdNCg7r9/fW3jgpVJbDmy5+PB2IYp4MXFelQ7izPrhkPHB+P5/PjhD5gCgCenx+VR/dODEwD+A3T7nqbxwf1HAAAAAElFTkSuQmCC';
    }
 
    /**
     * Gets menu tab for the Debugbar
     *
     * @return string
     */
    public function getTab()
    {
        if (!$this->_profilers)
            return 'No Profiler';
 
        foreach ($this->_profilers as $profiler) {
            $time = 0;
            foreach ($profiler as $event) {
                $time += $event->getElapsedSecs();
            }
            $profilerInfo[] = $profiler->count() . ' in ' . round($time*1000, 2)  . ' ms';
        }
        $html = implode(' / ', $profilerInfo);
 
        return $html;
    }
 
    /**
     * Gets content panel for the Debugbar
     *
     * @return string
     */
    public function getPanel()
    {
        if (!$this->_profilers)
            return '';
 
        $html = '
<h4>Database queries</h4>
 
';
 
        foreach ($this->_profilers as $name => $profiler) {
                $html .= '
<h4>Connection '.$name.'</h4>
<ol>';
                foreach ($profiler as $event) {
                    if (in_array($event->getName(), array('query', 'execute', 'exec'))) {
                        $info = htmlspecialchars($event->getQuery());
                    } else {
                        $info = '<em>' . htmlspecialchars($event->getName()) . '</em>';
                    }
 
                    $html .= '
<li><strong>[' . round($event->getElapsedSecs()*1000, 2) . ' ms]</strong> ';
                    $html .= $info;
 
                    $params = $event->getParams();
                    if(!empty($params)) {
                        $html .= '
<ul><em>bindings:</em>
<li>'. implode('</li>
<li>', $params) . '</li>
</ul>
 
';
                    }
                    $html .= '</li>
 
';
                }
                $html .= '</ol>
 
';
        }
 
        return $html;
    }
 
}
