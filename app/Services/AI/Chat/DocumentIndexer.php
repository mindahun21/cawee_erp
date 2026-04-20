<?php

namespace App\Services\AI\Chat;

use App\Services\AI\VectorStore\VectorStoreInterface;
use App\Services\AI\VectorStore\EmbeddingService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;
use Illuminate\Support\Str;

class DocumentIndexer
{
    public function __construct(
        protected VectorStoreInterface $vectorStore,
        protected EmbeddingService $embeddingService,
    ) {}

    /**
     * Index all documents from the given storage directory into the vector store.
     *
     * @param string $path Relative path under storage/.
     * @param bool $fresh Whether to clear all embeddings and re-index everything.
     */
    public function indexDirectory(string $path = 'app/ai-context', bool $fresh = false): void
    {
        $this->vectorStore->ensureSchema();

        $fullPath = storage_path($path);
        if (!File::exists($fullPath)) {
            Log::info("DocumentIndexer: Directory {$fullPath} does not exist, creating it.");
            File::makeDirectory($fullPath, 0755, true, true);
            return;
        }

        $files = File::allFiles($fullPath);
        
        if (empty($files)) {
            Log::info("DocumentIndexer: No files found in {$fullPath}");
            return;
        }

        // Fresh mode: Clear all previous embeddings for a clean re-index
        if ($fresh) {
            Log::info("DocumentIndexer: Fresh mode - clearing all existing embeddings");
            $this->vectorStore->truncate();
        }

        $totalChunks = 0;
        $processedFiles = 0;
        $skippedFiles = 0;

        foreach ($files as $file) {
            $source = $file->getRelativePathname();
            $extension = strtolower($file->getExtension());

            // Check if file needs re-indexing (incremental mode)
            if (!$fresh && !$this->shouldReindex($file, $source)) {
                Log::info("DocumentIndexer: Skipping unchanged file '{$source}'");
                $skippedFiles++;
                continue;
            }

            // Delete old embeddings for this file if it exists
            if (!$fresh) {
                $this->vectorStore->deleteBySource($source);
            }

            $processingSuccess = false;

            // Process images with Vision API
            if (in_array($extension, ['png', 'jpg', 'jpeg', 'webp'])) {
                $processingSuccess = $this->processImage($file, $source, $extension, $totalChunks);
                if ($processingSuccess) $processedFiles++;
                continue;
            }

            // Process videos with Gemini Video API
            if (in_array($extension, ['mp4', 'avi', 'mov', 'webm', 'mkv'])) {
                $processingSuccess = $this->processVideo($file, $source, $extension, $totalChunks);
                if ($processingSuccess) $processedFiles++;
                continue;
            }

            // Process PDFs with text extraction
            if ($extension === 'pdf') {
                $processingSuccess = $this->processPdf($file, $source, $totalChunks);
                if ($processingSuccess) $processedFiles++;
                continue;
            }

            // Process text files
            $content = $file->getContents();

            // Split content into manageable chunks (max ~1500 chars each)
            $chunks = $this->chunkText($content, 1500);

            Log::info("DocumentIndexer: Processing '{$source}' -> " . count($chunks) . " chunks");

            // Batch embed all chunks for this file
            $chunkTexts = array_map(fn(array $c) => $c['text'], $chunks);
            
            try {
                $embeddings = $this->embeddingService->embedBatch($chunkTexts);
            } catch (\Exception $e) {
                Log::error("DocumentIndexer: Failed to embed '{$source}': " . $e->getMessage());
                continue;
            }

            // Get file metadata for tracking
            $fileHash = md5_file($file->getRealPath());
            $fileSize = $file->getSize();
            $fileModified = \Carbon\Carbon::createFromTimestamp($file->getMTime());

            foreach ($chunks as $i => $chunk) {
                if (!isset($embeddings[$i]) || empty($embeddings[$i])) {
                    continue;
                }

                $this->vectorStore->store(
                    source: $source,
                    content: $chunk['text'],
                    embedding: $embeddings[$i],
                    metadata: [
                        'file_type' => $extension,
                        'chunk_index' => $i,
                        'total_chunks' => count($chunks),
                        'file_hash' => $fileHash,
                        'file_size' => $fileSize,
                        'file_modified_at' => $fileModified,
                    ],
                );

                $totalChunks++;
            }
            
            $processedFiles++;
        }

        // Build the search index after all data is loaded
        if (method_exists($this->vectorStore, 'buildIndex')) {
            $this->vectorStore->buildIndex();
        }

        Log::info("DocumentIndexer: Indexed {$totalChunks} chunks from {$processedFiles} files (skipped {$skippedFiles} unchanged).");
    }

    /**
     * Check if a file needs to be re-indexed based on hash comparison.
     */
    protected function shouldReindex(\SplFileInfo $file, string $source): bool
    {
        $fileHash = md5_file($file->getRealPath());
        $existing = $this->vectorStore->findBySource($source);
        
        if (!$existing) {
            return true; // New file, needs indexing
        }
        
        if ($existing->file_hash !== $fileHash) {
            return true; // File changed, needs re-indexing
        }
        
        return false; // File unchanged, skip
    }

    protected function processImage(\SplFileInfo $file, string $source, string $extension, int &$totalChunks): bool
    {
        try {
            Log::info("DocumentIndexer: Processing Image '{$source}' via Vision model.");
            
            // Add delay to avoid rate limiting (5 seconds for free tier)
            sleep(5);
            
            $imageData = base64_encode(file_get_contents($file->getRealPath()));
            $mime = mime_content_type($file->getRealPath());
            
            $response = Prism::text()
                ->using('gemini', 'gemini-2.5-flash')
                ->withSystemPrompt("Analyze this screenshot/image of our ERP module. Describe what module this is, the UI elements visible, the fields available, and what business workflow this represents. Output only the description text.")
                ->withMessages([
                    new \Prism\Prism\ValueObjects\Messages\UserMessage(
                        "Please describe this ERP screenshot.",
                        [new \Prism\Prism\ValueObjects\Media\Image(base64: $imageData, mimeType: $mime)]
                    )
                ])
                ->generate();
                
            $description = "Image Source: [{$source}]\n\nVisual Description:\n" . $response->text;
            
            // Generate embedding for the AI's description
            $embedding = $this->embeddingService->embedBatch([$description])[0];
            
            // Get file metadata
            $fileHash = md5_file($file->getRealPath());
            $fileSize = $file->getSize();
            $fileModified = \Carbon\Carbon::createFromTimestamp($file->getMTime());
            
            $this->vectorStore->store(
                source: $source,
                content: $description,
                embedding: $embedding,
                metadata: [
                    'file_type' => $extension,
                    'is_image' => true,
                    'file_hash' => $fileHash,
                    'file_size' => $fileSize,
                    'file_modified_at' => $fileModified,
                ],
            );
            
            $totalChunks++;
            return true;
        } catch (\Exception $e) {
            Log::error("DocumentIndexer: Failed to process image '{$source}': " . $e->getMessage());
            return false;
        }
    }

    protected function processVideo(\SplFileInfo $file, string $source, string $extension, int &$totalChunks): bool
    {
        try {
            Log::info("DocumentIndexer: Processing Video '{$source}' via Gemini Video API.");
            
            // Upload video file to Gemini File API
            $apiKey = env('GEMINI_API_KEY');
            $uploadUrl = "https://generativelanguage.googleapis.com/upload/v1beta/files?key={$apiKey}";
            
            // First, initiate the upload
            $initResponse = \Illuminate\Support\Facades\Http::timeout(120)
                ->withHeaders([
                    'X-Goog-Upload-Protocol' => 'resumable',
                    'X-Goog-Upload-Command' => 'start',
                    'X-Goog-Upload-Header-Content-Length' => filesize($file->getRealPath()),
                    'X-Goog-Upload-Header-Content-Type' => mime_content_type($file->getRealPath()),
                ])
                ->post($uploadUrl, [
                    'file' => [
                        'display_name' => $source,
                    ]
                ]);

            if ($initResponse->failed()) {
                throw new \RuntimeException('Failed to initiate video upload: ' . $initResponse->body());
            }

            $uploadUri = $initResponse->header('X-Goog-Upload-URL');
            
            // Upload the actual file content
            $uploadResponse = \Illuminate\Support\Facades\Http::timeout(300)
                ->withHeaders([
                    'Content-Length' => filesize($file->getRealPath()),
                    'X-Goog-Upload-Offset' => '0',
                    'X-Goog-Upload-Command' => 'upload, finalize',
                ])
                ->withBody(file_get_contents($file->getRealPath()), mime_content_type($file->getRealPath()))
                ->put($uploadUri);

            if ($uploadResponse->failed()) {
                throw new \RuntimeException('Failed to upload video: ' . $uploadResponse->body());
            }

            $fileData = $uploadResponse->json('file');
            $fileUri = $fileData['uri'] ?? null;

            if (!$fileUri) {
                throw new \RuntimeException('No file URI returned from upload');
            }

            // Wait for video processing (poll until ACTIVE)
            $maxAttempts = 30;
            $attempt = 0;
            $fileState = 'PROCESSING';
            
            while ($fileState === 'PROCESSING' && $attempt < $maxAttempts) {
                sleep(2);
                $statusResponse = \Illuminate\Support\Facades\Http::timeout(30)
                    ->get("https://generativelanguage.googleapis.com/v1beta/{$fileData['name']}?key={$apiKey}");
                
                if ($statusResponse->successful()) {
                    $fileState = $statusResponse->json('state', 'PROCESSING');
                }
                $attempt++;
            }

            if ($fileState !== 'ACTIVE') {
                throw new \RuntimeException("Video processing timeout or failed. State: {$fileState}");
            }

            // Now analyze the video with Gemini
            $response = Prism::text()
                ->using('gemini', 'gemini-2.5-flash')
                ->withSystemPrompt("Analyze this video of our ERP system. Describe what module/workflow is shown, key actions demonstrated, UI elements visible, and the business process being explained. Output only the description text.")
                ->withMessages([
                    new \Prism\Prism\ValueObjects\Messages\UserMessage(
                        "Please describe what this ERP training video demonstrates.",
                        [\Prism\Prism\ValueObjects\Media\Video::fromFileId($fileData['name'])]
                    )
                ])
                ->generate();
                
            $description = "Video Source: [{$source}]\n\nVideo Content Description:\n" . $response->text;
            
            // Generate embedding for the AI's description
            $embedding = $this->embeddingService->embedBatch([$description])[0];
            
            // Get file metadata
            $fileHash = md5_file($file->getRealPath());
            $fileSize = $file->getSize();
            $fileModified = \Carbon\Carbon::createFromTimestamp($file->getMTime());
            
            $this->vectorStore->store(
                source: $source,
                content: $description,
                embedding: $embedding,
                metadata: [
                    'file_type' => $extension,
                    'is_video' => true,
                    'gemini_file_uri' => $fileUri,
                    'file_hash' => $fileHash,
                    'file_size' => $fileSize,
                    'file_modified_at' => $fileModified,
                ],
            );
            
            $totalChunks++;
            
            // Optionally delete the uploaded file from Gemini
            \Illuminate\Support\Facades\Http::timeout(30)
                ->delete("https://generativelanguage.googleapis.com/v1beta/{$fileData['name']}?key={$apiKey}");
            
            return true;
                
        } catch (\Exception $e) {
            Log::error("DocumentIndexer: Failed to process video '{$source}': " . $e->getMessage());
            return false;
        }
    }

    protected function processPdf(\SplFileInfo $file, string $source, int &$totalChunks): bool
    {
        try {
            Log::info("DocumentIndexer: Processing PDF '{$source}' via text extraction.");
            
            // Try using pdftotext command if available (most Linux systems have it)
            $textContent = null;
            
            if (function_exists('shell_exec')) {
                $pdfPath = escapeshellarg($file->getRealPath());
                $textContent = shell_exec("pdftotext {$pdfPath} - 2>/dev/null");
            }
            
            // Fallback: Use Gemini Vision API to process PDF as images
            if (empty(trim($textContent ?? ''))) {
                Log::info("DocumentIndexer: pdftotext not available, using Gemini Vision API for PDF '{$source}'");
                
                // Upload PDF to Gemini File API
                $apiKey = env('GEMINI_API_KEY');
                $uploadUrl = "https://generativelanguage.googleapis.com/upload/v1beta/files?key={$apiKey}";
                
                // Initiate upload
                $initResponse = \Illuminate\Support\Facades\Http::timeout(120)
                    ->withHeaders([
                        'X-Goog-Upload-Protocol' => 'resumable',
                        'X-Goog-Upload-Command' => 'start',
                        'X-Goog-Upload-Header-Content-Length' => filesize($file->getRealPath()),
                        'X-Goog-Upload-Header-Content-Type' => 'application/pdf',
                    ])
                    ->post($uploadUrl, [
                        'file' => [
                            'display_name' => $source,
                        ]
                    ]);

                if ($initResponse->failed()) {
                    throw new \RuntimeException('Failed to initiate PDF upload: ' . $initResponse->body());
                }

                $uploadUri = $initResponse->header('X-Goog-Upload-URL');
                
                // Upload the file
                $uploadResponse = \Illuminate\Support\Facades\Http::timeout(300)
                    ->withHeaders([
                        'Content-Length' => filesize($file->getRealPath()),
                        'X-Goog-Upload-Offset' => '0',
                        'X-Goog-Upload-Command' => 'upload, finalize',
                    ])
                    ->withBody(file_get_contents($file->getRealPath()), 'application/pdf')
                    ->put($uploadUri);

                if ($uploadResponse->failed()) {
                    throw new \RuntimeException('Failed to upload PDF: ' . $uploadResponse->body());
                }

                $fileData = $uploadResponse->json('file');
                $fileUri = $fileData['uri'] ?? null;

                if (!$fileUri) {
                    throw new \RuntimeException('No file URI returned from PDF upload');
                }

                // Wait for processing
                $maxAttempts = 20;
                $attempt = 0;
                $fileState = 'PROCESSING';
                
                while ($fileState === 'PROCESSING' && $attempt < $maxAttempts) {
                    sleep(2);
                    $statusResponse = \Illuminate\Support\Facades\Http::timeout(30)
                        ->get("https://generativelanguage.googleapis.com/v1beta/{$fileData['name']}?key={$apiKey}");
                    
                    if ($statusResponse->successful()) {
                        $fileState = $statusResponse->json('state', 'PROCESSING');
                    }
                    $attempt++;
                }

                if ($fileState !== 'ACTIVE') {
                    throw new \RuntimeException("PDF processing timeout or failed. State: {$fileState}");
                }

                // Extract text using Gemini
                $response = Prism::text()
                    ->using('gemini', 'gemini-2.5-flash')
                    ->withSystemPrompt("Extract all text content from this PDF document. Preserve the structure and formatting as much as possible. Output only the extracted text.")
                    ->withMessages([
                        new \Prism\Prism\ValueObjects\Messages\UserMessage(
                            "Please extract all text from this PDF.",
                            [\Prism\Prism\ValueObjects\Media\Document::fromFileId($fileData['name'])]
                        )
                    ])
                    ->generate();
                    
                $textContent = $response->text;
                
                // Clean up uploaded file
                \Illuminate\Support\Facades\Http::timeout(30)
                    ->delete("https://generativelanguage.googleapis.com/v1beta/{$fileData['name']}?key={$apiKey}");
            }
            
            if (empty(trim($textContent))) {
                throw new \RuntimeException('No text content extracted from PDF');
            }
            
            // Chunk and embed the extracted text
            $chunks = $this->chunkText($textContent, 1500);
            Log::info("DocumentIndexer: Extracted text from PDF '{$source}' -> " . count($chunks) . " chunks");
            
            $chunkTexts = array_map(fn(array $c) => $c['text'], $chunks);
            $embeddings = $this->embeddingService->embedBatch($chunkTexts);
            
            // Get file metadata
            $fileHash = md5_file($file->getRealPath());
            $fileSize = $file->getSize();
            $fileModified = \Carbon\Carbon::createFromTimestamp($file->getMTime());
            
            foreach ($chunks as $i => $chunk) {
                if (!isset($embeddings[$i]) || empty($embeddings[$i])) {
                    continue;
                }

                $this->vectorStore->store(
                    source: $source,
                    content: $chunk['text'],
                    embedding: $embeddings[$i],
                    metadata: [
                        'file_type' => 'pdf',
                        'chunk_index' => $i,
                        'total_chunks' => count($chunks),
                        'file_hash' => $fileHash,
                        'file_size' => $fileSize,
                        'file_modified_at' => $fileModified,
                    ],
                );

                $totalChunks++;
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("DocumentIndexer: Failed to process PDF '{$source}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Split text into overlapping chunks for better semantic retrieval.
     *
     * @param string $text The full text to split.
     * @param int $maxChars Maximum characters per chunk.
     * @param int $overlap Number of overlapping characters between chunks.
     * @return array Array of ['text' => string, 'start' => int].
     */
    protected function chunkText(string $text, int $maxChars = 1500, int $overlap = 200): array
    {
        $text = trim($text);
        $length = mb_strlen($text);

        if ($length <= $maxChars) {
            return [['text' => $text, 'start' => 0]];
        }

        $chunks = [];
        $start = 0;

        while ($start < $length) {
            $end = min($start + $maxChars, $length);
            $chunkText = mb_substr($text, $start, $end - $start);

            // Try to break at a paragraph or sentence boundary
            if ($end < $length) {
                $lastParagraph = mb_strrpos($chunkText, "\n\n");
                $lastSentence = mb_strrpos($chunkText, '. ');

                if ($lastParagraph !== false && $lastParagraph > $maxChars * 0.5) {
                    $chunkText = mb_substr($chunkText, 0, $lastParagraph);
                    $end = $start + $lastParagraph;
                } elseif ($lastSentence !== false && $lastSentence > $maxChars * 0.5) {
                    $chunkText = mb_substr($chunkText, 0, $lastSentence + 2);
                    $end = $start + $lastSentence + 2;
                }
            }

            $chunks[] = ['text' => trim($chunkText), 'start' => $start];
            
            // STRICT GUARANTEE: $start MUST universally advance to prevent infinite loops.
            $nextStart = $end - $overlap;
            if ($nextStart <= $start) {
                $nextStart = $end; // No overlap, just move strictly forward
            }
            
            $start = $nextStart;

            if ($start >= $length) break;
        }

        return $chunks;
    }
}
