<div class="space-y-4">
    <!-- Dropzone -->
    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors"
         x-data="{
             isDragging: false,
             handleDrop(e) {
                 this.isDragging = false;
                 const files = Array.from(e.dataTransfer.files);
                 if (files.length > {{ $maxFiles }}) {
                     alert('Maksimum {{ $maxFiles }} dosya yükleyebilirsiniz.');
                     return;
                 }
                 @this.set('files', files);
             }
         }"
         x-on:dragover.prevent="isDragging = true"
         x-on:dragleave.prevent="isDragging = false"
         x-on:drop.prevent="handleDrop"
         :class="{ 'border-blue-400 bg-blue-50 dark:bg-blue-900': isDragging }">
        
        <div class="space-y-4">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $dropzoneText }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                    Maksimum {{ $maxFiles }} dosya, her biri {{ $maxFileSize }}MB'dan küçük
                </p>
            </div>
            
            <input type="file" 
                   wire:model="files" 
                   multiple 
                   accept="{{ $allowedTypes }}"
                   class="hidden" 
                   id="file-input"
                   max="{{ $maxFiles }}">
            
            <label for="file-input" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 cursor-pointer">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Dosya Seç
            </label>
        </div>
    </div>

    <!-- Selected Files Preview -->
    @if(count($files) > 0)
        <div class="space-y-2">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Seçilen Dosyalar</h4>
            
            <div class="space-y-2">
                @foreach($files as $index => $file)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded border">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $file->getClientOriginalName() }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $this->formatFileSize($file->getSize()) }}
                                </p>
                            </div>
                        </div>
                        
                        <button wire:click="removeFile({{ $index }})"
                                class="text-red-500 hover:text-red-700 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Upload Progress -->
                    @if(isset($uploadProgress[$index]))
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                 style="width: {{ $uploadProgress[$index] }}%"></div>
                        </div>
                    @endif
                @endforeach
            </div>
            
            <div class="flex justify-end">
                <button wire:click="uploadFiles"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>Dosyaları Yükle</span>
                    <span wire:loading>Yükleniyor...</span>
                </button>
            </div>
        </div>
    @endif

    <!-- Uploaded Files -->
    @if(count($uploadedFiles) > 0)
        <div class="space-y-2">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Yüklenen Dosyalar</h4>
            
            <div class="space-y-2">
                @foreach($uploadedFiles as $index => $file)
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900 rounded border border-green-200 dark:border-green-700">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $file['original_name'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $this->formatFileSize($file['size']) }} • {{ $file['uploaded_at']->format('d.m.Y H:i') }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <a href="{{ asset('storage/' . $file['path']) }}" 
                               target="_blank"
                               class="text-blue-500 hover:text-blue-700 p-1"
                               title="Dosyayı görüntüle">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            
                            <button wire:click="removeUploadedFile({{ $index }})"
                                    class="text-red-500 hover:text-red-700 p-1"
                                    title="Dosyayı sil">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Loading States -->
    <div wire:loading wire:target="files" class="text-center py-4">
        <div class="inline-flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Dosyalar kontrol ediliyor...
        </div>
    </div>
</div>

<script>
// Prevent default drag behaviors
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    document.addEventListener(eventName, preventDefaults, false);
    document.body.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}
</script>