<?php

class Fab_ObjectStorage_Object
{

    /** @var string path and name of the object */
    public $pathname;

    /** @var string path containing the object */
    public $path;

    /** @var string base name of the object */
    public $name;

    /** @var int object size in bytes */
    public $size;

    /** @var int last modified date/time in Unix epoch (seconds since 1970) */
    public $lastModified;

}
