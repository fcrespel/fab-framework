<?php

class Fab_Captcha_QA extends Zend_Captcha_Word
{
    /** @var array */
    protected $_questionsAndAnswers = array();
    
    /**
     * Get questions & answers to use.
     * @return array
     */
    public function getQuestionsAndAnswers()
    {
        return $this->_questionsAndAnswers;
    }

    /**
     * Set questions & answers to use.
     * @param array $questionsAndAnswers 
     * @return self
     */
    public function setQuestionsAndAnswers($questionsAndAnswers)
    {
        $this->_questionsAndAnswers = $questionsAndAnswers;
        return $this;
    }
    
    /**
     * Generate new random word
     *
     * @return string
     */
    protected function _generateWord()
    {
        $maxIndex = count($this->_questionsAndAnswers) - 1;
        if ($maxIndex < 0)
            throw new Zend_Captcha_Exception('No questions have been defined in Fab_Captcha_QA');
        
        $index = rand(0, $maxIndex);
        return $this->_questionsAndAnswers[$index];
    }
    
    /**
     * Render the captcha
     *
     * @param  Zend_View_Interface $view
     * @param  mixed $element
     * @return string
     */
    public function render(Zend_View_Interface $view = null, $element = null)
    {
        $qa = $this->getWord();
        return '<p>' . $qa['question'] . '</p>';
    }
    
    /**
     * Validate the word
     *
     * @see    Zend_Validate_Interface::isValid()
     * @param  mixed $value
     * @return boolean
     */
    public function isValid($value, $context = null)
    {
        if (!is_array($value) && !is_array($context)) {
            $this->_error(self::MISSING_VALUE);
            return false;
        }
        if (!is_array($value) && is_array($context)) {
            $value = $context;
        }

        $name = $this->getName();
        if (isset($value[$name])) {
            $value = $value[$name];
        }

        if (!isset($value['input']) || empty($value['input'])) {
            $this->_error(self::MISSING_VALUE);
            return false;
        }
        $input = preg_replace('/\s+/', ' ', trim(strtolower($value['input'])));
        $this->_setValue($input);

        if (!isset($value['id']) || empty($value['id'])) {
            $this->_error(self::MISSING_ID);
            return false;
        }
        $this->_id = $value['id'];
        
        $qa = $this->getWord();
        if (!empty($qa)) {
            $answers = $qa['answers'];
            if (is_string($answers)) {
                $answers = explode("\n", $answers);
            }
            foreach ($answers as $answer) {
                $answer = preg_replace('/\s+/', ' ', trim(strtolower($answer)));
                if ($input === $answer) {
                    return true;
                }
            }
        }
        
        $this->_error(self::BAD_CAPTCHA);
        return false;
    }
}
