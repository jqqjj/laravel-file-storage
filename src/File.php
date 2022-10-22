<?php

namespace Jqqjj\LaravelFileStorage;

use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Jqqjj\LaravelFileStorage\Exception\StorageException;

class File
{
    protected $model;

    public function __construct($model) {
        $this->model = $model;
    }

    public function __call($name, $args)
    {
        return $this->model->$name(...$args);
    }

    public function __set($name, $value)
    {
        $this->model->$name = $value;
    }

    public function __get($name)
    {
        return $this->model->$name;
    }

    /**
     * @throws StorageException
     */
    public function url()
    {
        $relativeDirectory = implode(DIRECTORY_SEPARATOR, [
            'upload', substr($this->model->md5,0,2), substr($this->model->md5, 2,2),
        ]);
        $fullDirectory = Storage::disk('public')->path($relativeDirectory);
        if (!FileFacade::isDirectory($fullDirectory) && !FileFacade::makeDirectory($fullDirectory, 0640, true, true)) {
            throw new StorageException();
        }

        $sourceFullPath = Storage::path($this->model->path);
        $target = $fullDirectory . DIRECTORY_SEPARATOR . basename($this->model->path);
        if (!Storage::disk('public')->exists($relativeDirectory . DIRECTORY_SEPARATOR . basename($this->model->path))) {
            FileFacade::link($sourceFullPath, $target);
        }

        return '/storage/' . str_replace('\\', '/', $relativeDirectory . DIRECTORY_SEPARATOR . basename($this->model->path));
    }

    public function thumbUrl($options)
    {
        $options = $this->optionsSafe($options);
        $signature = $this->calcSignature($options);

        $cachePath = $this->getCachePath($signature);
        if (!Storage::disk('public')->exists($cachePath)) {
            $dir = storage_path('app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.$cachePath);
            if (!FileFacade::isDirectory(dirname($dir)) && !FileFacade::makeDirectory(dirname($dir), 0640, true, true)) {
                throw new StorageException();
            }
            $image = Image::make(storage_path('app'. DIRECTORY_SEPARATOR . $this->model->path));
            if (!empty($options['width']) && !empty($options['height']) && $options['width'] > 0 && $options['height'] > 0) {
                $image->fit($options['width'], $options['height'], function ($constraint) {//按照比例多原始图片最大修剪
                    $constraint->upsize();
                });
            }
            if (!empty($options['blur']) && $options['blur'] > 0) {
                $image->blur($options['blur']);
            }
            $image->save($dir);
        }

        return '/storage/' . str_replace('\\', '/', $cachePath);
    }

    private function optionsSafe($options)
    {
        return array_intersect_key($options, array_flip([
            'width', 'height', 'blur',
        ]));
    }

    private function calcSignature($options)
    {
        ksort($options);
        $salt = '';
        foreach ($options as $k=>$v) {
            $salt .= $k.$v;
        }
        return md5($salt);
    }

    private function getCachePath($signature)
    {
        $name = substr(basename($this->model->path), 0, strrpos(basename($this->model->path), "."));
        $ext = substr(basename($this->model->path), strrpos(basename($this->model->path), "."));
        $relativeDirectory = implode(DIRECTORY_SEPARATOR, [
            'cache', substr($this->model->md5,0,2), substr($this->model->md5, 2,2), $name,
        ]);
        return $relativeDirectory . DIRECTORY_SEPARATOR . $signature . $ext;
    }
}
