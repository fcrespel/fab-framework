<?php

use ZxcvbnPhp\Zxcvbn;

class Fab_Validate_PasswordStrength extends Zend_Validate_Abstract
{
    /**
     * Error codes
     * @const string
     */
    const WEAK_PASSWORD = 'weakPassword';

    /**
     * Error messages
     * @var array
     */
    protected $_messageTemplates = array(
        self::WEAK_PASSWORD => 'Password is not strong enough. %warning%',
    );

    /**
     * Error message variables
     * @var array
     */
    protected $_messageVariables = array(
        'warning' => '_warning',
        'suggestions' => '_suggestions',
    );

    /** @var int */
    protected $_minScore = 2;

    /** @var string */
    protected $_warning = '';

    /** @var array */
    protected $_suggestions = [];

    /**
     * Constructor.
     * @param array $options 
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Set options for this validator.
     * @param array $options
     * @return self
     */
    public function setOptions(array $options = array())
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
    * Get minimum score required.
    * @return int
    */
    public function getMinScore()
    {
        return $this->_minScore;
    }

    /**
     * Set minimum score required.
     * @param int $minScore
     * @return self
     */
    public function setMinScore($minScore)
    {
        $this->_minScore = $minScore;
        return $this;
    }

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @param  array $context
     * @return boolean
     * @throws Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($value, $context = null)
    {
        $this->_setValue((string) $value);

        $zxcvbn = new Zxcvbn();
        $result = $zxcvbn->passwordStrength($value);
        if ($result['score'] < $this->getMinScore()) {
            $this->_warning = isset($result['feedback']['warning']) ? $result['feedback']['warning'] : '';
            $this->_suggestions = isset($result['feedback']['suggestions']) ? $result['feedback']['suggestions'] : [];
            $this->_error(self::WEAK_PASSWORD);
            return false;
        }

        return true;
    }
}
