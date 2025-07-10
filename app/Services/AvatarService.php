<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAvatar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Class AvatarService
 * 
 * Handles user avatar operations including:
 * - Avatar upload and processing
 * - Image resizing and cropping
 * - File storage management
 * - Avatar removal
 * - Default avatar assignment
 * 
 * @package App\Services
 */
class AvatarService
{
    /**
     * Avatar storage disk
     * 
     * @var string
     */
    protected string $disk = 'public';

    /**
     * Avatar storage path
     * 
     * @var string
     */
    protected string $storagePath = 'avatars';

    /**
     * Maximum file size in bytes (2MB)
     * 
     * @var int
     */
    protected int $maxFileSize = 2097152;

    /**
     * Allowed file types
     * 
     * @var array
     */
    protected array $allowedTypes = ['jpg', 'jpeg', 'png'];

    /**
     * Avatar dimensions
     * 
     * @var array
     */
    protected array $dimensions = [
        'thumbnail' => ['width' => 50, 'height' => 50],
        'small' => ['width' => 100, 'height' => 100],
        'medium' => ['width' => 200, 'height' => 200],
        'large' => ['width' => 400, 'height' => 400]
    ];

    /**
     * Upload and process user avatar
     * 
     * @param User $user
     * @param UploadedFile $file
     * @param array|null $cropData
     * @return array
     * @throws Exception
     */
    public function uploadAvatar(User $user, UploadedFile $file, ?array $cropData = null): array
    {
        try {
            // Validate file
            $this->validateFile($file);

            DB::beginTransaction();

            // Remove existing avatar if exists
            if ($user->avatar) {
                $this->removeExistingAvatar($user->avatar);
            }

            // Generate unique filename
            $filename = $this->generateFilename($file);
            $storedFilename = $user->id . '_' . time() . '_' . $filename;
            
            // Process and store the image
            $processedImage = $this->processImage($file, $cropData);
            $filePath = $this->storagePath . '/' . $storedFilename;
            
            // Store the main image
            Storage::disk($this->disk)->put($filePath, $processedImage->encode());
            
            // Generate variants
            $variants = $this->generateVariants($processedImage, $user->id);
            
            // Get file information
            $fileInfo = $this->getFileInfo($file, $processedImage);
            
            // Create avatar record
            $avatarData = [
                'user_id' => $user->id,
                'filename' => $filename,
                'stored_filename' => $storedFilename,
                'file_path' => $filePath,
                'file_url' => Storage::disk($this->disk)->url($filePath),
                'mime_type' => $file->getMimeType(),
                'file_extension' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'file_hash' => hash_file('sha256', $file->getRealPath()),
                'width' => $processedImage->width(),
                'height' => $processedImage->height(),
                'aspect_ratio' => round($processedImage->width() / $processedImage->height(), 2),
                'variants' => $variants,
                'thumbnail_path' => $variants['thumbnail']['path'],
                'small_path' => $variants['small']['path'],
                'medium_path' => $variants['medium']['path'],
                'large_path' => $variants['large']['path'],
                'variant_urls' => [
                    'thumbnail' => $variants['thumbnail']['url'],
                    'small' => $variants['small']['url'],
                    'medium' => $variants['medium']['url'],
                    'large' => $variants['large']['url']
                ],
                'is_active' => true,
                'is_default' => false,
                'is_approved' => true,
                'is_public' => false,
                'status' => 'active',
                'upload_source' => 'user_upload',
                'upload_metadata' => [
                    'original_name' => $file->getClientOriginalName(),
                    'upload_time' => now()->toISOString(),
                    'crop_data' => $cropData
                ],
                'is_processed' => true,
                'processed_at' => now(),
                'quality_score' => $this->calculateQualityScore($processedImage),
                'storage_driver' => 'local',
                'storage_disk' => $this->disk,
                'first_used_at' => now(),
                'last_used_at' => now()
            ];

            $avatar = UserAvatar::create($avatarData);

            DB::commit();

            Log::info('Avatar uploaded successfully', [
                'user_id' => $user->id,
                'avatar_id' => $avatar->id,
                'filename' => $filename
            ]);

            return [
                'id' => $avatar->id,
                'filename' => $filename,
                'file_url' => $avatarData['file_url'],
                'file_size' => $avatarData['file_size'],
                'variants' => $avatarData['variant_urls']
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Avatar upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception('Failed to upload avatar: ' . $e->getMessage());
        }
    }

    /**
     * Remove user avatar
     * 
     * @param User $user
     * @return bool
     * @throws Exception
     */
    public function removeAvatar(User $user): bool
    {
        try {
            if (!$user->avatar) {
                return true;
            }

            DB::beginTransaction();

            $avatar = $user->avatar;
            
            // Remove files from storage
            $this->removeExistingAvatar($avatar);
            
            // Delete avatar record
            $avatar->delete();

            DB::commit();

            Log::info('Avatar removed successfully', [
                'user_id' => $user->id,
                'avatar_id' => $avatar->id
            ]);

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Avatar removal failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            throw new Exception('Failed to remove avatar: ' . $e->getMessage());
        }
    }

    /**
     * Get default avatar URL
     * 
     * @param User $user
     * @return string
     */
    public function getDefaultAvatarUrl(User $user): string
    {
        // Generate a default avatar based on user initials
        $initials = strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1));
        
        // You can implement a service like Gravatar or generate SVG avatars
        // For now, return a placeholder URL
        return "https://ui-avatars.com/api/?name={$initials}&size=400&background=667eea&color=ffffff&bold=true";
    }

    /**
     * Validate uploaded file
     * 
     * @param UploadedFile $file
     * @throws Exception
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            throw new Exception('File size exceeds 2MB limit.');
        }

        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG and PNG files are allowed.');
        }

        // Check if file is actually an image
        $imageInfo = getimagesize($file->getRealPath());
        if (!$imageInfo) {
            throw new Exception('Invalid image file.');
        }

        // Check image dimensions (minimum 50x50)
        if ($imageInfo[0] < 50 || $imageInfo[1] < 50) {
            throw new Exception('Image must be at least 50x50 pixels.');
        }
    }

    /**
     * Process image with optional cropping
     * 
     * @param UploadedFile $file
     * @param array|null $cropData
     * @return \Intervention\Image\Image
     */
    protected function processImage(UploadedFile $file, ?array $cropData = null)
    {
        $image = Image::make($file->getRealPath());

        // Apply cropping if provided
        if ($cropData && isset($cropData['x'], $cropData['y'], $cropData['width'], $cropData['height'])) {
            $image->crop(
                (int) $cropData['width'],
                (int) $cropData['height'],
                (int) $cropData['x'],
                (int) $cropData['y']
            );
        }

        // Resize to maximum 400x400 while maintaining aspect ratio
        $image->resize(400, 400, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Optimize image quality
        $image->sharpen(10);
        
        return $image;
    }

    /**
     * Generate image variants
     * 
     * @param \Intervention\Image\Image $image
     * @param string $userId
     * @return array
     */
    protected function generateVariants($image, string $userId): array
    {
        $variants = [];
        $timestamp = time();

        foreach ($this->dimensions as $size => $dimensions) {
            $variant = clone $image;
            $variant->resize($dimensions['width'], $dimensions['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $filename = "{$userId}_{$timestamp}_{$size}.jpg";
            $path = $this->storagePath . '/variants/' . $filename;
            
            Storage::disk($this->disk)->put($path, $variant->encode('jpg', 85));
            
            $variants[$size] = [
                'path' => $path,
                'url' => Storage::disk($this->disk)->url($path),
                'width' => $variant->width(),
                'height' => $variant->height()
            ];
        }

        return $variants;
    }

    /**
     * Remove existing avatar files
     * 
     * @param UserAvatar $avatar
     */
    protected function removeExistingAvatar(UserAvatar $avatar): void
    {
        try {
            // Remove main file
            if ($avatar->file_path && Storage::disk($this->disk)->exists($avatar->file_path)) {
                Storage::disk($this->disk)->delete($avatar->file_path);
            }

            // Remove variants
            if ($avatar->variants) {
                foreach ($avatar->variants as $variant) {
                    if (isset($variant['path']) && Storage::disk($this->disk)->exists($variant['path'])) {
                        Storage::disk($this->disk)->delete($variant['path']);
                    }
                }
            }

            Log::info('Existing avatar files removed', [
                'avatar_id' => $avatar->id,
                'user_id' => $avatar->user_id
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to remove some avatar files', [
                'avatar_id' => $avatar->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate unique filename
     * 
     * @param UploadedFile $file
     * @return string
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::random(40) . '.' . $extension;
    }

    /**
     * Get file information
     * 
     * @param UploadedFile $file
     * @param \Intervention\Image\Image $image
     * @return array
     */
    protected function getFileInfo(UploadedFile $file, $image): array
    {
        return [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $image->width(),
            'height' => $image->height()
        ];
    }

    /**
     * Calculate image quality score
     * 
     * @param \Intervention\Image\Image $image
     * @return int
     */
    protected function calculateQualityScore($image): int
    {
        $width = $image->width();
        $height = $image->height();
        $pixels = $width * $height;
        
        // Basic quality scoring based on resolution
        if ($pixels >= 160000) { // 400x400 or higher
            return 100;
        } elseif ($pixels >= 40000) { // 200x200 or higher
            return 80;
        } elseif ($pixels >= 10000) { // 100x100 or higher
            return 60;
        } else {
            return 40;
        }
    }
}