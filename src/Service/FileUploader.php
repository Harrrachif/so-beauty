<?php

namespace App\Service;

use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FileUploader
{
   private $targetDirectory;
   private $slugger;

   public function __construct($targetDirectory, SluggerInterface $slugger)
   {
       $this->targetDirectory = $targetDirectory;
       $this->slugger = $slugger;
   }

   public function upload(UploadedFile $file)
   {
       $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
       $safeFilename = $this->slugger->slug($originalFilename);
       $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

       try {
           $file->move($this->getTargetDirectory(), $fileName);
       } catch (FileException $e) {
           // handle exception if something happens during file upload
           throw new FileException('An error occurred while uploading the file: ' . $e->getMessage());
       }

       return $fileName;
   }

   public function getTargetDirectory()
   {
       return $this->targetDirectory;
   }
}