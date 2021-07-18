<?php

namespace Kvnc;

use InvalidArgumentException;
use RuntimeException;

class Env
{    
    /**
     * path .env file location
     *
     * @var mixed
     */
    protected $path;
    
    /**
     * __construct
     *
     * @param  mixed $path
     * @return void
     */
    public function __construct(string $path)
    {
        if(file_exists($path)){
            $this->path = $path;
            if(!is_readable($path)){
                throw new RuntimeException(sprintf('%s path is not readble',$path));
            }
        }else{
            throw new InvalidArgumentException(sprintf('%s path is not found',$path));
        }
        
    }

    public function init():void
    {
        
        $readLines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($readLines as $line) {

            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

}