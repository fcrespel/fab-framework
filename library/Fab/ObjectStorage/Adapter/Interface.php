<?php

use Psr\Http\Message\StreamInterface;

interface Fab_ObjectStorage_Adapter_Interface
{

    /**
     * List objects at the given path.
     * @param string $path path prefix
     * @return Fab_ObjectStorage_Object[] objects list
     */
    public function list($path);

    /**
     * Check if an object exists at the given path.
     * @param string $path object path
     * @return bool true if the object exists, false otherwise
     */
    public function exists($path);

    /**
     * Get an object at the given path.
     * @param string $path object path
     * @return Fab_ObjectStorage_Object|bool object or false if it doesn't exist
     */
    public function get($path);

    /**
     * Get the content of an object at the given path.
     * @param string $path object path
     * @return StreamInterface|bool data stream or false if the object doesn't exist
     */
    public function getContent($path);

    /**
     * Put the content of an object at the given path.
     * This will create or update the object if it exists.
     * @param string $path object path
     * @param StreamInterface|resource|string $content data stream
     * @return bool true if the object was uploaded, false otherwise
     */
    public function put($path, $content);

    /**
     * Delete an object at the given path.
     * @param string $path object path
     * @return bool true if the object was deleted, false otherwise
     */
    public function delete($path);

    /**
     * Copy an object from a source path to a destination path.
     * @param string $srcPath source object path
     * @param string $dstPath destination object path
     * @return bool true if the object was copied, false otherwise
     */
    public function copy($srcPath, $dstPath);

    /**
     * Move an object from a source path to a destination path.
     * @param string $srcPath source object path
     * @param string $dstPath destination object path
     * @return bool true if the object was moved, false otherwise
     */
    public function move($srcPath, $dstPath);

}
