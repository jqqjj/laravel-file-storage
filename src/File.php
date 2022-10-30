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
        $signature = $this->calcSignature(array_merge($options, ['url'=>$this->model->path]));

        $cachePath = $this->getCachePath($signature);
        if (!Storage::disk('public')->exists($cachePath)) {
            $dir = storage_path('app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.$cachePath);
            if (!FileFacade::isDirectory(dirname($dir)) && !FileFacade::makeDirectory(dirname($dir), 0755, true, true)) {
                throw new StorageException();
            }
            $image = Image::make(storage_path('app'. DIRECTORY_SEPARATOR . $this->model->path));
            if (!empty($options['width']) && !empty($options['height']) && $options['width'] > 0 && $options['height'] > 0) {
                $image->fit($options['width'], $options['height'], function ($constraint) {//按照比例多原始图片最大修剪
                    $constraint->upsize();
                });
            } else if (!empty($options['max_width']) && !empty($options['max_height'])
                && $options['max_width'] > 0 && $options['max_height'] > 0) {
                $scaleWidth = max($image->getWidth() / $options['max_width'], 1);
                $scaleHeight = max($image->getHeight() / $options['max_height'], 1);

                if ($scaleWidth > $scaleHeight) {
                    $image->resize($options['max_width'],null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                } else {
                    $image->resize(null, $options['max_height'], function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
//                $maxScale = max($scaleWidth, $scaleHeight);
//                $image->resize(floor($image->getWidth()/$maxScale), floor($image->getHeight()/$maxScale));
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
            'width', 'height', 'blur', 'max_width', 'max_height'
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
            'cache', substr($this->model->md5,0,2), substr($this->model->md5, 2,2),
        ]);
        return $relativeDirectory . DIRECTORY_SEPARATOR . substr($signature,0,-4).substr($name, 0,4) . $ext;
    }
}
