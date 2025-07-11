<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

/**
 * Class UploadController
 * 
 * Handles file uploads for admin interface, particularly for rich text editors.
 * Provides secure file upload functionality with validation and storage management.
 * 
 * @package App\Http\Controllers\Admin
 */
class UploadController extends Controller
{
    /**
     * Upload image for TinyMCE editor.
     * 
     * Handles image uploads from TinyMCE rich text editor with validation,
     * secure storage, and proper response formatting.
     * 
     * @param Request $request HTTP request containing uploaded file
     * @return JsonResponse JSON response with upload status and file location
     */
    public function uploadImage(Request $request): JsonResponse
    {
        try {
            // Validate the uploaded file
            $validator = Validator::make($request->all(), [
                'file' => [
                    'required',
                    'file',
                    'image',
                    'mimes:jpeg,jpg,png,gif,webp',
                    'max:5120' // 5MB max file size
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file: ' . $validator->errors()->first()
                ], 422);
            }

            $file = $request->file('file');
            
            // Generate unique filename
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            
            // Store file in public disk under content/images directory
            $path = $file->storeAs('content/images', $filename, 'public');
            
            if (!$path) {
                throw new Exception('Failed to store uploaded file');
            }

            // Generate public URL for the uploaded image
            $url = Storage::url($path);
            
            return response()->json([
                'success' => true,
                'location' => $url,
                'message' => 'Image uploaded successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload file for content attachments.
     * 
     * Handles general file uploads for content attachments with broader
     * file type support and validation.
     * 
     * @param Request $request HTTP request containing uploaded file
     * @return JsonResponse JSON response with upload status and file information
     */
    public function uploadFile(Request $request): JsonResponse
    {
        try {
            // Validate the uploaded file
            $validator = Validator::make($request->all(), [
                'file' => [
                    'required',
                    'file',
                    'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar',
                    'max:10240' // 10MB max file size
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file: ' . $validator->errors()->first()
                ], 422);
            }

            $file = $request->file('file');
            
            // Generate unique filename while preserving original name
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $filename = Str::slug($originalName) . '_' . time() . '.' . $extension;
            
            // Store file in public disk under content/files directory
            $path = $file->storeAs('content/files', $filename, 'public');
            
            if (!$path) {
                throw new Exception('Failed to store uploaded file');
            }

            // Generate public URL and gather file information
            $url = Storage::url($path);
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            
            return response()->json([
                'success' => true,
                'url' => $url,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $fileSize,
                'mime_type' => $mimeType,
                'message' => 'File uploaded successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete uploaded file.
     * 
     * Removes uploaded file from storage with proper validation
     * and security checks.
     * 
     * @param Request $request HTTP request containing file path to delete
     * @return JsonResponse JSON response with deletion status
     */
    public function deleteFile(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'path' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid request: ' . $validator->errors()->first()
                ], 422);
            }

            $path = $request->input('path');
            
            // Security check: ensure path is within allowed directories
            if (!Str::startsWith($path, ['content/images/', 'content/files/'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized file path'
                ], 403);
            }

            // Check if file exists and delete it
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                
                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }
}