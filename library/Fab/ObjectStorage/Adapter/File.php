<?php

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\StreamWrapper;

class Fab_ObjectStorage_Adapter_File implements Fab_ObjectStorage_Adapter_Interface
{

    public function list($path)
    {
        $items = array();
        $iterator = new DirectoryIterator($path);
        $iterator = new RegexIterator($iterator, '#(^|/|\\\\)[^\.][^/\\\\]*$#');
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $items[] = $this->_mapObject($file);
            }
        }
        return $items;
    }

    public function exists($path)
    {
        return file_exists($path);
    }

    public function get($path)
    {
        if (file_exists($path)) {
            return $this->_mapObject(new SplFileInfo($path));
        } else {
            return false;
        }
    }

    public function getContent($path)
    {
        if (file_exists($path)) {
            return GuzzleHttp\Psr7\stream_for(fopen($path, 'rb'));
        } else {
            return false;
        }
    }

    public function put($path, $content)
    {
        if ($content instanceof StreamInterface) {
            $content = StreamWrapper::getResource($content);
        }
        if (file_put_contents($path, $content) !== false) {
            return $this->_mapObject(new SplFileInfo($path));
        } else {
            return false;
        }
    }

    public function delete($path)
    {
        if (file_exists($path)) {
            return unlink($path);
        } else {
            return false;
        }
    }

    public function copy($srcPath, $dstPath)
    {
        if (file_exists($srcPath)) {
            return copy($srcPath, $dstPath);
        } else {
            return false;
        }
    }

    public function move($srcPath, $dstPath)
    {
        if (file_exists($srcPath)) {
            return rename($srcPath, $dstPath);
        } else {
            return false;
        }
    }

    protected function _mapObject(SplFileInfo $file)
    {
        $item = new Fab_ObjectStorage_Object();
        $item->pathname = $file->getPathname();
        $item->path = $file->getPath();
        $item->name = $file->getFilename();
        $item->size = $file->getSize();
        $item->lastModified = $file->getMTime();
        return $item;
    }

}
