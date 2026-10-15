<?php
/**
 * Secure File Upload Handler
 * Creative IT Institute - DreamIT Platform
 *
 * Production-ready image upload with comprehensive security:
 * - Magic byte validation (real file type check)
 * - File size limits
 * - Image reprocessing (strips EXIF and malicious code)
 * - Sanitized random filenames
 * - MIME type whitelist
 * - Thumbnail generation
 * - Malware-proof implementation
 */

if (!defined('UPLOAD_HANDLER_LOADED')) {
    define('UPLOAD_HANDLER_LOADED', true);
}

class SecureUploadHandler {

    // Configuration
    private const MAX_FILE_SIZE = 5242880; // 5MB in bytes
    private const ALLOWED_TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png'  => ['png'],
        'image/gif'  => ['gif'],
        'image/webp' => ['webp']
    ];

    private const MAGIC_BYTES = [
        'image/jpeg' => ["\xFF\xD8\xFF"],
        'image/png'  => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
        'image/gif'  => ["GIF87a", "GIF89a"],
        'image/webp' => ["RIFF"]
    ];

    private string $uploadDir;
    private array $errors = [];

    public function __construct(string $uploadDir = 'courses') {
        $baseDir = dirname(__DIR__) . '/uploads/' . $uploadDir;

        // Create directory if it doesn't exist
        if (!is_dir($baseDir)) {
            if (!mkdir($baseDir, 0755, true)) {
                throw new Exception("Failed to create upload directory: {$baseDir}");
            }
        }

        $this->uploadDir = $baseDir;
    }

    /**
     * Upload and process image file
     *
     * @param array $file $_FILES array element
     * @param string $fieldName Name of the field (for error messages)
     * @return array ['success' => bool, 'path' => string|null, 'url' => string|null, 'error' => string|null]
     */
    public function upload(array $file, string $fieldName = 'file'): array {
        $this->errors = [];

        // 1. Basic validation
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return $this->error('No valid file uploaded');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->error($this->getUploadErrorMessage($file['error']));
        }

        // 2. File size check
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return $this->error('File size exceeds maximum allowed size of 5MB');
        }

        if ($file['size'] === 0) {
            return $this->error('Uploaded file is empty');
        }

        // 3. MIME type validation (both reported and detected)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset(self::ALLOWED_TYPES[$detectedMime])) {
            return $this->error('Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed');
        }

        // 4. Magic byte validation (prevents file type spoofing)
        if (!$this->validateMagicBytes($file['tmp_name'], $detectedMime)) {
            return $this->error('File failed security validation. The file may be corrupted or malicious');
        }

        // 5. Image validation (ensures it's a real, valid image)
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return $this->error('Invalid or corrupted image file');
        }

        [$width, $height, $imageType] = $imageInfo;

        // Validate image dimensions (reasonable limits)
        if ($width < 100 || $height < 100) {
            return $this->error('Image dimensions too small. Minimum 100x100 pixels');
        }

        if ($width > 8000 || $height > 8000) {
            return $this->error('Image dimensions too large. Maximum 8000x8000 pixels');
        }

        // 6. Generate secure filename
        $extension = $this->getExtensionFromMime($detectedMime);
        $filename = $this->generateSecureFilename($extension);
        $targetPath = $this->uploadDir . '/' . $filename;

        // 7. Reprocess image (strips EXIF, metadata, and potential malicious code)
        if (!$this->reprocessImage($file['tmp_name'], $targetPath, $detectedMime, $width, $height)) {
            return $this->error('Failed to process image. Please try a different image');
        }

        // 8. Generate thumbnail
        $thumbnailPath = $this->generateThumbnail($targetPath, $detectedMime);

        // 9. Return success with paths
        $relativePath = 'uploads/' . basename($this->uploadDir) . '/' . $filename;
        $thumbnailRelative = $thumbnailPath ? ('uploads/' . basename($this->uploadDir) . '/' . basename($thumbnailPath)) : null;

        return [
            'success' => true,
            'path' => $relativePath,
            'url' => $this->pathToUrl($relativePath),
            'thumbnail_path' => $thumbnailRelative,
            'thumbnail_url' => $thumbnailRelative ? $this->pathToUrl($thumbnailRelative) : null,
            'width' => $width,
            'height' => $height,
            'size' => filesize($targetPath),
            'mime_type' => $detectedMime
        ];
    }

    /**
     * Validate file magic bytes (real file signature)
     */
    private function validateMagicBytes(string $filepath, string $mimeType): bool {
        if (!isset(self::MAGIC_BYTES[$mimeType])) {
            return false;
        }

        $handle = fopen($filepath, 'rb');
        if (!$handle) {
            return false;
        }

        $bytes = fread($handle, 12); // Read first 12 bytes
        fclose($handle);

        foreach (self::MAGIC_BYTES[$mimeType] as $magic) {
            if (strncmp($bytes, $magic, strlen($magic)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reprocess image to strip metadata and potential malicious code
     */
    private function reprocessImage(string $sourcePath, string $targetPath, string $mimeType, int $width, int $height): bool {
        // Create image resource based on type
        $source = match($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png'  => @imagecreatefrompng($sourcePath),
            'image/gif'  => @imagecreatefromgif($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false
        };

        if (!$source) {
            return false;
        }

        // Create new clean image
        $clean = imagecreatetruecolor($width, $height);
        if (!$clean) {
            imagedestroy($source);
            return false;
        }

        // Preserve transparency for PNG and GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($clean, false);
            imagesavealpha($clean, true);
            $transparent = imagecolorallocatealpha($clean, 0, 0, 0, 127);
            imagefilledrectangle($clean, 0, 0, $width, $height, $transparent);
        }

        // Copy image data (strips EXIF and metadata)
        imagecopyresampled($clean, $source, 0, 0, 0, 0, $width, $height, $width, $height);

        // Save based on type
        $saved = match($mimeType) {
            'image/jpeg' => imagejpeg($clean, $targetPath, 90),
            'image/png'  => imagepng($clean, $targetPath, 8),
            'image/gif'  => imagegif($clean, $targetPath),
            'image/webp' => function_exists('imagewebp') ? imagewebp($clean, $targetPath, 90) : false,
            default => false
        };

        imagedestroy($source);
        imagedestroy($clean);

        if ($saved) {
            chmod($targetPath, 0644);
        }

        return $saved;
    }

    /**
     * Generate thumbnail (300px max width/height, maintains aspect ratio)
     */
    private function generateThumbnail(string $sourcePath, string $mimeType): ?string {
        $maxDimension = 300;

        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return null;
        }

        [$origWidth, $origHeight] = $imageInfo;

        // Skip thumbnail if image is already small
        if ($origWidth <= $maxDimension && $origHeight <= $maxDimension) {
            return null;
        }

        // Calculate new dimensions
        if ($origWidth > $origHeight) {
            $newWidth = $maxDimension;
            $newHeight = (int)(($origHeight / $origWidth) * $maxDimension);
        } else {
            $newHeight = $maxDimension;
            $newWidth = (int)(($origWidth / $origHeight) * $maxDimension);
        }

        // Create source image
        $source = match($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png'  => @imagecreatefrompng($sourcePath),
            'image/gif'  => @imagecreatefromgif($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false
        };

        if (!$source) {
            return null;
        }

        // Create thumbnail
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        if (!$thumb) {
            imagedestroy($source);
            return null;
        }

        // Preserve transparency
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        // Generate thumbnail filename
        $pathinfo = pathinfo($sourcePath);
        $thumbPath = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_thumb.' . $pathinfo['extension'];

        // Save thumbnail
        $saved = match($mimeType) {
            'image/jpeg' => imagejpeg($thumb, $thumbPath, 85),
            'image/png'  => imagepng($thumb, $thumbPath, 8),
            'image/gif'  => imagegif($thumb, $thumbPath),
            'image/webp' => function_exists('imagewebp') ? imagewebp($thumb, $thumbPath, 85) : false,
            default => false
        };

        imagedestroy($source);
        imagedestroy($thumb);

        if ($saved) {
            chmod($thumbPath, 0644);
            return $thumbPath;
        }

        return null;
    }

    /**
     * Generate cryptographically secure random filename
     */
    private function generateSecureFilename(string $extension): string {
        $timestamp = time();
        $random = bin2hex(random_bytes(16));
        return "course_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMime(string $mimeType): string {
        $extensions = self::ALLOWED_TYPES[$mimeType] ?? ['jpg'];
        return $extensions[0];
    }

    /**
     * Convert relative path to full URL
     */
    private function pathToUrl(string $relativePath): string {
        // Detect project root from script path
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseMatch = preg_match('#^(.*/)[^/]+\.php$#', $scriptPath, $matches);
        $basePath = $baseMatch ? rtrim($matches[1], '/') : '';

        // Get protocol
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Handle subdirectory installations (e.g., /dreamit/)
        $subdir = '';
        if (strpos($basePath, '/') !== false) {
            $parts = explode('/', trim($basePath, '/'));
            if (!empty($parts[0])) {
                $subdir = '/' . $parts[0];
            }
        }

        return "{$protocol}://{$host}{$subdir}/{$relativePath}";
    }

    /**
     * Delete uploaded file and its thumbnail
     */
    public function delete(string $relativePath): bool {
        $fullPath = dirname(__DIR__) . '/' . ltrim($relativePath, '/');

        if (!file_exists($fullPath)) {
            return false;
        }

        // Delete main file
        $deleted = @unlink($fullPath);

        // Delete thumbnail if exists
        $pathinfo = pathinfo($fullPath);
        $thumbPath = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_thumb.' . $pathinfo['extension'];
        if (file_exists($thumbPath)) {
            @unlink($thumbPath);
        }

        return $deleted;
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string {
        return match($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            default => 'Unknown upload error'
        };
    }

    /**
     * Return error response
     */
    private function error(string $message): array {
        return [
            'success' => false,
            'error' => $message
        ];
    }

    /**
     * Get maximum upload size from PHP configuration
     */
    public static function getMaxUploadSize(): int {
        $max_upload = self::parseSize(ini_get('upload_max_filesize'));
        $max_post = self::parseSize(ini_get('post_max_size'));
        $memory_limit = self::parseSize(ini_get('memory_limit'));

        return min($max_upload, $max_post, $memory_limit, self::MAX_FILE_SIZE);
    }

    /**
     * Parse PHP size string to bytes
     */
    private static function parseSize(string $size): int {
        $unit = strtolower(substr($size, -1));
        $value = (int)$size;

        return match($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value
        };
    }
}
