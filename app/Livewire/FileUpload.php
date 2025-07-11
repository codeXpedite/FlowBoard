<?php

namespace App\Livewire;

use App\Services\SecurityService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

class FileUpload extends Component
{
    use WithFileUploads;

    public $files = [];
    public $uploadedFiles = [];
    public $maxFiles = 5;
    public $maxFileSize = 10; // MB
    public $allowedTypes = 'image/*,application/pdf,.doc,.docx,.txt,.zip';
    public $dropzoneText = 'Dosyaları buraya sürükleyin veya tıklayın';
    public $uploadProgress = [];

    protected SecurityService $securityService;

    public function boot(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function updatedFiles()
    {
        $this->validateFiles();
    }

    public function validateFiles()
    {
        $errors = [];
        
        foreach ($this->files as $index => $file) {
            if (!$file) continue;
            
            $fileErrors = $this->securityService->validateFileUpload($file);
            
            if (!empty($fileErrors)) {
                $errors["files.{$index}"] = $fileErrors;
                unset($this->files[$index]);
            }
        }
        
        if (!empty($errors)) {
            foreach ($errors as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    session()->flash('error', $error);
                }
            }
        }
    }

    public function removeFile($index)
    {
        unset($this->files[$index]);
        $this->files = array_values($this->files);
    }

    public function removeUploadedFile($index)
    {
        if (isset($this->uploadedFiles[$index])) {
            // Delete file from storage
            $filePath = $this->uploadedFiles[$index]['path'];
            if (\Storage::exists($filePath)) {
                \Storage::delete($filePath);
            }
            
            unset($this->uploadedFiles[$index]);
            $this->uploadedFiles = array_values($this->uploadedFiles);
        }
    }

    public function uploadFiles()
    {
        if (empty($this->files)) {
            session()->flash('error', 'Lütfen yüklenecek dosya seçin.');
            return;
        }

        foreach ($this->files as $index => $file) {
            try {
                $this->uploadProgress[$index] = 0;
                
                // Generate secure filename
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $filename = pathinfo($originalName, PATHINFO_FILENAME);
                $secureFilename = \Str::slug($filename) . '_' . time() . '.' . $extension;
                
                // Store file
                $path = $file->storeAs('uploads', $secureFilename, 'public');
                
                $this->uploadedFiles[] = [
                    'original_name' => $originalName,
                    'filename' => $secureFilename,
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_at' => now(),
                ];
                
                $this->uploadProgress[$index] = 100;
                
            } catch (\Exception $e) {
                session()->flash('error', "Dosya yükleme hatası: {$originalName}");
                \Log::error('File upload error', [
                    'file' => $originalName,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Clear files after upload
        $this->files = [];
        $this->uploadProgress = [];
        
        session()->flash('success', count($this->uploadedFiles) . ' dosya başarıyla yüklendi.');
        $this->dispatch('files-uploaded', files: $this->uploadedFiles);
    }

    public function getFileIcon($mimeType)
    {
        $iconMap = [
            'application/pdf' => 'fa-file-pdf',
            'application/msword' => 'fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
            'text/plain' => 'fa-file-text',
            'application/zip' => 'fa-file-archive',
            'image/jpeg' => 'fa-file-image',
            'image/png' => 'fa-file-image',
            'image/gif' => 'fa-file-image',
        ];

        return $iconMap[$mimeType] ?? 'fa-file';
    }

    public function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function render()
    {
        return view('livewire.file-upload');
    }
}