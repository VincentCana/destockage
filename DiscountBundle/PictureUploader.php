<?php
namespace DiscountBundle;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class PictureUploader
{
    private $targetDir;

    public function __construct($targetDir)
    {
        $this->targetDir = $targetDir;
    }

    public function upload(UploadedFile $picture)
    {
        $fileName = md5(uniqid()).'.'.$picture->guessExtension();

        $picture->move($this->targetDir, $fileName);

        return $fileName;
    }

    public function getTargetDir()
    {
        return $this->targetDir;
    }
}