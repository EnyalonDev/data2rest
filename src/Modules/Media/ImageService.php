<?php

namespace App\Modules\Media;

use Imagick;
use Exception;
use App\Core\Logger;
use App\Core\Config;

/**
 * ImageService
 * Provides image optimization and processing using Imagick.
 */
class ImageService
{
    /** @var int Maximum dimension (width or height) allowed for images */
    private $maxDimension;

    public function __construct()
    {
        // Load settings from database or use defaults
        $this->maxDimension = (int) Config::getSetting('media_optimize_max_dimension', 1080);
    }

    /**
     * Processes an image: resizes if needed, optimizes quality, and converts format if configured.
     * 
     * @param string $sourcePath Path to the source file (usually a temp file).
     * @param string $destinationDir Directory where the file should be saved.
     * @param string $filename Original filename.
     * @param bool $forceOriginal If true, skip format conversion (user has upload_original permission).
     * @return string Final filename (might have different extension).
     */
    public function process($sourcePath, $destinationDir, $filename, $forceOriginal = false)
    {
        $destinationDir = rtrim($destinationDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $pi = pathinfo($filename);
        $extension = strtolower($pi['extension'] ?? '');
        $basename = $pi['filename'];
        $finalFilename = $filename;

        try {
            // Check if it's an image first
            $imageInfo = @getimagesize($sourcePath);
            if (!$imageInfo) {
                $this->fallback($sourcePath, $destinationDir . $finalFilename);
                return $finalFilename;
            }

            $mime = $imageInfo['mime'];
            $supportedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];

            if (!in_array($mime, $supportedMimes)) {
                $this->fallback($sourcePath, $destinationDir . $finalFilename);
                return $finalFilename;
            }

            if (!class_exists('Imagick')) {
                throw new Exception("Imagick extension not found.");
            }

            $imagick = new Imagick($sourcePath);

            // 1. Handle Orientation
            $orientation = $imagick->getImageOrientation();
            switch ($orientation) {
                case Imagick::ORIENTATION_BOTTOMRIGHT:
                    $imagick->rotateImage("#000", 180);
                    break;
                case Imagick::ORIENTATION_RIGHTTOP:
                    $imagick->rotateImage("#000", 90);
                    break;
                case Imagick::ORIENTATION_LEFTBOTTOM:
                    $imagick->rotateImage("#000", -90);
                    break;
            }
            $imagick->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);

            // 2. Resize
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();

            if ($width > $this->maxDimension || $height > $this->maxDimension) {
                if ($width > $height) {
                    $imagick->resizeImage($this->maxDimension, 0, Imagick::FILTER_LANCZOS, 1);
                } else {
                    $imagick->resizeImage(0, $this->maxDimension, Imagick::FILTER_LANCZOS, 1);
                }
            }

            // 3. Formats and Optimization
            $priorityFormat = Config::getSetting('media_optimize_priority', 'webp'); // Changed default to 'webp'
            $quality = (int) Config::getSetting('media_optimize_quality', 85);
            $availableFormats = Imagick::queryFormats();

            // If user has permission to upload originals, force 'original' format
            if ($forceOriginal) {
                $priorityFormat = 'original';
            }

            if ($priorityFormat === 'avif' && in_array('AVIF', $availableFormats)) {
                $imagick->setImageFormat('avif');
                $finalFilename = $basename . '.avif';
            } elseif (($priorityFormat === 'webp' || $priorityFormat === 'avif') && in_array('WEBP', $availableFormats)) {
                $imagick->setImageFormat('webp');
                $finalFilename = $basename . '.webp';
            }

            // If the extension changed, check for collision again
            if ($finalFilename !== $filename && file_exists($destinationDir . $finalFilename)) {
                $finalFilename = $basename . '-' . substr(uniqid(), -5) . '.' . pathinfo($finalFilename, PATHINFO_EXTENSION);
            }

            $imagick->setImageCompressionQuality($quality);
            $imagick->stripImage();

            if ($imagick->getImageFormat() === 'jpeg' || $imagick->getImageFormat() === 'jpg') {
                $imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE);
            }

            // 4. Save
            $success = $imagick->writeImage($destinationDir . $finalFilename);
            $imagick->clear();
            $imagick->destroy();

            if (!$success) {
                throw new Exception("Imagick failed to write image.");
            }

            return $finalFilename;

        } catch (Exception $e) {
            Logger::log('IMAGE_SERVICE_ERROR', [
                'error' => $e->getMessage(),
                'file' => $filename
            ]);

            $this->fallback($sourcePath, $destinationDir . $filename);
            return $filename;
        }
    }

    /**
     * Fallback mechanism: copies the file as-is.
     */
    private function fallback($sourcePath, $destinationPath)
    {
        // If it's a move_uploaded_file context, we might need to handle it differently
        // but since process() receives $sourcePath which is usually $file['tmp_name'],
        // copy() is safe enough if we are not restricted.
        // However, some servers restrict access to tmp folder unless using move_uploaded_file.
        if (is_uploaded_file($sourcePath)) {
            return move_uploaded_file($sourcePath, $destinationPath);
        }
        return copy($sourcePath, $destinationPath);
    }
}
