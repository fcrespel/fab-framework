<?php

class Fab_View_Helper_ProgressBar extends Zend_View_Helper_Abstract
{
    protected $_defaults = array(
        'emptyLabel' => '',
        'fullLabel' => 'complete',
    );

    public function progressBar($min, $max, $value, $options = array())
    {
        $options = array_merge($this->_defaults, $options);

        // Calculate progress in percent
        $valuePercent = intval(($value - $min) * 100.0 / $max);
        if ($valuePercent <= 0) $valuePercent = 0;
        if ($valuePercent >= 100) $valuePercent = 100;

        // Generate min/max/value labels
        $minLabel = isset($options['minLabel']) ? $options['minLabel'] : $min;
        $maxLabel = isset($options['maxLabel']) ? $options['maxLabel'] : $max;
        if ($valuePercent == 0)
            $valueLabel = $options['emptyLabel'];
        else if ($valuePercent == 100)
            $valueLabel = $options['fullLabel'];
        else
            $valueLabel = $value;

        // Generate CSS class suffix
        if ($valuePercent == 0)
            $suffix = 'empty';
        else if ($valuePercent > 0 && $valuePercent < 25)
            $suffix = 'q1';
        else if ($valuePercent >= 25 && $valuePercent < 50)
            $suffix = 'q2';
        else if ($valuePercent >= 50 && $valuePercent < 75)
            $suffix = 'q3';
        else if ($valuePercent >= 75 && $valuePercent < 100)
            $suffix = 'q4';
        else if ($valuePercent == 100)
            $suffix = 'full';

        // Generate HTML output
        $progressMin = '<span class="progress-min">'.$minLabel.'</span>';
        $progressBar = '<span class="progress-bar"><span class="progress-bar-progress progress-bar-progress-'.$suffix.'" style="width: '.$valuePercent.'%">&nbsp;'.$valueLabel.'&nbsp;</span></span>';
        $progressMax = '<span class="progress-max">'.$maxLabel.'</span>';
        return '<div class="progress">' . $progressMin . $progressBar . $progressMax . '</div>';
    }
}
