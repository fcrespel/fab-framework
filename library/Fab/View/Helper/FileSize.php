<?php

class Fab_View_Helper_FileSize extends Zend_View_Helper_Abstract
{
    /** @var array */
    protected $_units = array(
        'bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'
    );

    /**
     * Get a human-readable file size representation.
     * @param int $size
     * @param array $units
     * @return string
     */
    public function fileSize($size, $units = array())
    {
        if (empty($size) && $size !== 0 && $size !== '0')
            return $size;
        
        if (empty($units))
            $units = $this->getUnits();
        
        $unit = 0;
        $value = $size;
        while ($value > 1024 && isset($units[$unit+1])) {
            $value /= 1024;
            $unit++;
        }
        
        return round($value, 2) . ' ' . $units[$unit];
    }
    
    /**
     * Get the unit names to use, starting from bytes.
     * @return array
     */
    public function getUnits()
    {
        return $this->_units;
    }
    
    /**
     * Set unit names to use, starting from bytes.
     * @param array $units
     * @return self
     */
    public function setUnits($units)
    {
        $this->_units = $units;
        return $this;
    }
}
