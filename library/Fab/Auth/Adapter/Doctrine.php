<?php

class Fab_Auth_Adapter_Doctrine extends Fab_Auth_Adapter_Abstract
{
    /** @var string */
    protected $_modelName = null;

    /** @var string */
    protected $_identityColumn = null;

    /** @var string */
    protected $_credentialColumn = null;

    /** @var mixed */
    protected $_credentialTransformer = null;
    
    /** @var string */
    protected $_lockColumn = null;

    /** @var Doctrine_Query */
    protected $_query = null;

    /** @var Doctrine_Record */
    protected $_record = null;

    /**
     * Get the Doctrine model name.
     * @return string
     */
    public function getModelName()
    {
        return $this->_modelName;
    }

    /**
     * Set the Doctrine model name.
     * @param string $modelName
     */
    public function setModelName($modelName)
    {
        $this->_modelName = $modelName;
    }

    /**
     * Get the column name to use to search the identity.
     * @return string
     */
    public function getIdentityColumn()
    {
        return $this->_identityColumn;
    }

    /**
     * Set the column name to use to search the identity.
     * @param string $identityColumn
     */
    public function setIdentityColumn($identityColumn)
    {
        $this->_identityColumn = $identityColumn;
    }

    /**
     * Get the column name to use to match the credential.
     * @return string
     */
    public function getCredentialColumn()
    {
        return $this->_credentialColumn;
    }

    /**
     * Set the column name to use to match the credential.
     * @param string $credentialColumn
     */
    public function setCredentialColumn($credentialColumn)
    {
        $this->_credentialColumn = $credentialColumn;
    }

    /**
     * Get the credential transformer function (or closure).
     * @return mixed
     */
    public function getCredentialTransformer()
    {
        return $this->_credentialTransformer;
    }

    /**
     * Set the credential transformer function (or closure).
     * @param mixed $credentialTransformer
     */
    public function setCredentialTransformer($credentialTransformer = null)
    {
        $this->_credentialTransformer = $credentialTransformer;
    }
    
    /**
     * Get the column name to use to check if the account is locked.
     * @return string
     */
    public function getLockColumn()
    {
        return $this->_lockColumn;
    }

    /**
     * Set the column name to use to check if the account is locked.
     * @param string $lockColumn
     */
    public function setLockColumn($lockColumn)
    {
        $this->_lockColumn = $lockColumn;
    }

    /**
     * Get the identity query.
     * @return Doctrine_Query
     */
    public function getIdentityQuery()
    {
        if ($this->_query === null) {
            $this->_query = Doctrine_Query::create()
                            ->select('*')
                            ->from($this->getModelName())
                            ->where($this->getIdentityColumn() . ' = :identity');
        }
        return $this->_query;
    }

    /**
     * Set or reset the identity query.
     * @param Doctrine_Query|null $query
     */
    public function setIdentityQuery($query = null)
    {
        $this->_query = $query;
    }

    /**
     * Get the authenticated identity record.
     * @return Doctrine_Record
     */
    public function getRecord()
    {
        return $this->_record;
    }

    /**
     * Get the authenticated identity record.
     * This method mimics Zend_Auth_Adapter_Ldap
     * @return Doctrine_Record
     */
    public function getAccountObject()
    {
        return $this->getRecord();
    }

    /**
     * Get the authenticated identity record.
     * This method mimics Zend_Auth_Adapter_DbTable
     * @return Doctrine_Record
     */
    public function getResultRowObject()
    {
        return $this->getRecord();
    }

    /**
     * Authenticate the user.
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $this->_validateParams();

        // Initialize Zend_Auth_Result params
        $code = Zend_Auth_Result::FAILURE;
        $identity = '';
        $messages = array();
        $messages[0] = '';
        $messages[1] = '';

        $query = $this->getIdentityQuery();
        $result = $query->execute(array(':identity' => $this->getIdentity()));
        if ($result->count() == 0) {
            // No match for identity
            $code = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
            $messages[0] = 'Account not found.';
            
        } else if ($result->count() > 1) {
            // Multiple results = ambiguous identity
            $code = Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;
            $messages[0] = 'More than one account matches the supplied identity.';
            
        } else {
            // Identity found, check credentials
            $credential = $this->getCredential();

            // Transform the credential if necessary (MD5, SHA-1, etc.)
            $transformer = $this->getCredentialTransformer();
            if (is_callable($transformer)) {
                $credential = call_user_func($transformer, $credential);
            }

            // Match with the credential column
            if ($result->getFirst()->get($this->getCredentialColumn()) === $credential) {
                if ($this->getLockColumn() !== null && $result->getFirst()->get($this->getLockColumn())) {
                    // Locked account
                    $code = Zend_Auth_Result::FAILURE_UNCATEGORIZED;
                    $messages[0] = 'Account is locked. Please contact an administrator.';
                } else {
                    // Success!
                    $code = Zend_Auth_Result::SUCCESS;
                    $messages[0] = 'Authentication successful.';
                    $identity = $this->getIdentity();
                    $this->_record = $result->getFirst();
                }
            } else {
                // Credentials don't match
                $code = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
                $messages[0] = 'Invalid credentials.';
            }
        }

        return new Zend_Auth_Result($code, $identity, $messages);
    }

    /**
     * Validate parameters configured on this instance
     * before performing authentication.
     * @throws Zend_Auth_Adapter_Exception
     */
    protected function _validateParams()
    {
        if (empty($this->_modelName)) {
            throw new Zend_Auth_Adapter_Exception('A model name must be supplied for the ' . get_class() . ' authentication adapter.');
        } else if (empty($this->_identityColumn)) {
            throw new Zend_Auth_Adapter_Exception('An identity column must be supplied for the ' . get_class() . ' authentication adapter.');
        } else if (empty($this->_credentialColumn)) {
            throw new Zend_Auth_Adapter_Exception('A credential column must be supplied for the ' . get_class() . ' authentication adapter.');
        }
        parent::_validateParams();
    }

}
