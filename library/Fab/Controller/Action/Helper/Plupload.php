<?php

class Fab_Controller_Action_Helper_Plupload extends Zend_Controller_Action_Helper_Abstract
{
    /** @var string */
    protected $_uploadDir;

    /** @var boolean */
    protected $_cleanupUploadDir = true;

    /** @var int */
    protected $_maxFileAge = 18000;
    
    /** @var int */
    protected $_maxFileSize = 0;
    
    /** @var string[] */
    protected $_allowedExtensions = array();

    /**
     * Get the upload directory name.
     * @return string
     */
    public function getUploadDir()
    {
        if ($this->_uploadDir === null) {
            $this->_uploadDir = ini_get('upload_tmp_dir') . DIRECTORY_SEPARATOR . 'plupload';
        }
        return $this->_uploadDir;
    }

    /**
     * Set the upload directory name.
     * @param string $uploadDir
     * @return self 
     */
    public function setUploadDir($uploadDir)
    {
        $this->_uploadDir = $uploadDir;
        return $this;
    }

    /**
     * Check whether old files should be removed from the upload directory
     * if they are older than maxFileAge.
     * @return boolean
     */
    public function isCleanupUploadDir()
    {
        return $this->_cleanupUploadDir;
    }

    /**
     * Set whether old files should be removed from the upload directory
     * if they are older than maxFileAge.
     * @param boolean $cleanupUploadDir 
     * @return self 
     */
    public function setCleanupUploadDir($cleanupUploadDir)
    {
        $this->_cleanupUploadDir = $cleanupUploadDir;
        return $this;
    }

    /**
     * Get the maximum file age before cleanup.
     * @return int
     */
    public function getMaxFileAge()
    {
        return $this->_maxFileAge;
    }

    /**
     * Set the maximum file age before cleanup.
     * @param int $maxFileAge 
     * @return self 
     */
    public function setMaxFileAge($maxFileAge)
    {
        $this->_maxFileAge = $maxFileAge;
        return $this;
    }

    /**
     * Get the maximum file size.
     * @return int
     */
    public function getMaxFileSize()
    {
        return $this->_maxFileSize;
    }

    /**
     * Set the maximum file size.
     * @param int $maxFileSize
     * @return self 
     */
    public function setMaxFileSize($maxFileSize)
    {
        $this->_maxFileSize = $maxFileSize;
        return $this;
    }
    
    /**
     * Get the list of allowed file extensions.
     * @return string[]
     */
    public function getAllowedExtensions()
    {
        return $this->_allowedExtensions;
    }

    /**
     * Set the list of allowed file extensions.
     * @param string[] $allowedExtensions
     * @return self
     */
    public function setAllowedExtensions(array $allowedExtensions)
    {
        $this->_allowedExtensions = $allowedExtensions;
        return $this;
    }

    /**
     * Direct helper call, forwarded to handleUpload()
     */
    public function direct()
    {
        $this->handleUpload();
    }

    /**
     * Handle file upload.
     */
    public function handleUpload()
    {
        // Get options
        $targetDir = $this->getUploadDir();
        $cleanupTargetDir = $this->isCleanupUploadDir();
        $maxFileAge = $this->getMaxFileAge();
        $maxFileSize = $this->getMaxFileSize();
        $allowedExtensions = $this->getAllowedExtensions();

        // Get parameters
        $request = $this->getRequest();
        $chunk = $request->getParam('chunk', 0);
        $chunks = $request->getParam('chunks', 0);
        $fileName = $request->getParam('name', '');

        // Clean the fileName for security reasons
        $fileName = preg_replace('/[^\w\._-]+/', '_', $fileName);
        
        // Make sure the file extension is allowed
        $fileExt = pathinfo(strtolower($fileName), PATHINFO_EXTENSION);
        if (!in_array($fileExt, $allowedExtensions))
            throw new Fab_Controller_Action_Exception('Invalid file extension: ' . $fileName, 104);

        // Make sure the fileName is unique but only if chunking is disabled
        if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
            $ext = strrpos($fileName, '.');
            $fileName_a = substr($fileName, 0, $ext);
            $fileName_b = substr($fileName, $ext);

            $count = 1;
            while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
                $count++;

            $fileName = $fileName_a . '_' . $count . $fileName_b;
        }

        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

        // Create target dir
        if (!file_exists($targetDir))
            @mkdir($targetDir);

        // Remove old temp files    
        if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir))) {
            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
                    @unlink($tmpfilePath);
                }
            }

            closedir($dir);
        } else {
            throw new Fab_Controller_Action_Exception('Failed to open temp directory.', 100);
        }

        // Look for the content type header
        $contentType = $request->getServer('CONTENT_TYPE', $request->getServer('HTTP_CONTENT_TYPE'));

        // Handle non multipart uploads (older WebKit versions didn't support multipart in HTML5)
        if (strpos($contentType, "multipart") !== false) {
            if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                // Open temp file
                $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
                if ($out) {
                    // Read binary input stream and append it to temp file
                    $in = fopen($_FILES['file']['tmp_name'], "rb");

                    if ($in) {
                        while ($buff = fread($in, 4096))
                            fwrite($out, $buff);
                    } else {
                        throw new Fab_Controller_Action_Exception('Failed to open input stream.', 101);
                    }
                    fclose($in);
                    fclose($out);
                    @unlink($_FILES['file']['tmp_name']);
                } else {
                    throw new Fab_Controller_Action_Exception('Failed to open output stream.', 102);
                }
            } else {
                throw new Fab_Controller_Action_Exception('Failed to move uploaded file.', 103);
            }
        } else {
            // Open temp file
            $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
            if ($out) {
                // Read binary input stream and append it to temp file
                $in = fopen("php://input", "rb");

                if ($in) {
                    while ($buff = fread($in, 4096))
                        fwrite($out, $buff);
                } else {
                    throw new Fab_Controller_Action_Exception('Failed to open input stream.', 101);
                }

                fclose($in);
                fclose($out);
            } else {
                throw new Fab_Controller_Action_Exception('Failed to open output stream.', 102);
            }
        }

        // Check if file has been uploaded
        if (!$chunks || $chunk == $chunks - 1) {
            // Strip the temp .part suffix off 
            rename("{$filePath}.part", $filePath);
            
            // Check the final file size
            if ($maxFileSize > 0 && filesize($filePath) > $maxFileSize) {
                @unlink($filePath);
                throw new Fab_Controller_Action_Exception('File too large: ' . $fileName, 105);
            }
        }

        return array(
            'chunk'     => $chunk,
            'chunks'    => $chunks,
            'fileName'  => $fileName,
            'filePath'  => $filePath,
            'complete'  => (!$chunks || $chunk == $chunks - 1),
        );
    }
}
