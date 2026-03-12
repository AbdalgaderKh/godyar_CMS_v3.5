<?php

class Cache
{

protected static bool $enabled = true;

protected static string $path = '';

public static function init(array $config=[]):void
{

if(isset($config['enabled']))
static::$enabled=(bool)$config['enabled'];

$base = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);

static::$path = $config['path'] ?? $base.'/cache';

static::ensurePath();

}

public static function get(string $key,$default=null)
{

if(!static::$enabled) return $default;

$file=static::file($key);

if(!is_file($file)) return $default;

$data=include $file;

if(!is_array($data)) return $default;

if($data['expires']<time()){

@unlink($file);
return $default;

}

return $data['value'];

}

public static function put(string $key,$value,int $seconds=300):void
{

if(!static::$enabled) return;

static::ensurePath();

$data=[
'expires'=>time()+$seconds,
'value'=>$value
];

file_put_contents(static::file($key),'<?php return '.var_export($data,true).';');

}

public static function remember(string $key,int $seconds,callable $callback)
{

$val=static::get($key);

if($val!==null) return $val;

$val=$callback();

static::put($key,$val,$seconds);

return $val;

}

protected static function file(string $key):string
{

return static::$path.'/'.hash('sha256',$key).'.phpcache';

}

protected static function ensurePath():void
{

if(!is_dir(static::$path))
mkdir(static::$path,0755,true);

}

}