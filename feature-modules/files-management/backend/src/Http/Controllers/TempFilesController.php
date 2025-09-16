<?php

namespace FilesManagement\FilesManagementModule\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use FilesManagement\FilesManagementModule\Http\Requests\TempFilesRequest;
use FilesManagement\FilesManagementModule\Http\Requests\UploadFilesRequest;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TempFilesController extends Controller
{
    public function upload_temp_files(TempFilesRequest $request)
    {
        $validated = $request->validated();
        $files = $validated['files'];
        $fileIds = [];
        $tempFiles = [];
        $single = $validated['single'] ?? false;

        if ($single) {
            auth()->user()->clearMediaCollection($validated['collection_name']);
        }

        foreach ($files as $file) {
            $response = auth()->user()->addMedia($file)->toMediaCollection($validated['collection_name']);
            $response->temp = true;
            $response->save();
            if ($response) {
                $fileIds[] = $response->id;
                $tempFiles[] = $response;
            } else {
                return response()->json(['message' => 'An error occurred.'], 500);
            }
        }
        return response()->json(['message' => 'Files uploaded successfully.', 'file_ids' => $fileIds, 'temp_files' => $tempFiles]);
    }

    public function remove_temp_file(Request $request)
    {
        $validated = $request->validate([
            'file_id' => 'required|exists:media,id'
        ]);

        $media = Media::find($validated['file_id']);
        if ($media) {
            $media->delete();
            return response()->json(['message' => 'File removed successfully.']);
        }
        return response()->json(['message' => 'An error occurred.'], 500);
    }

    public function upload_files(UploadFilesRequest $request)
    {
        $validated = $request->validated();
        $single = $validated['single'] ?? false;
        $modelClass = "App\\Models\\" . $validated['model_type'];
        if (class_exists($modelClass)) {    
            $model = $modelClass::findOrFail($validated['model_id']);
            if ($single) {
                $model->clearMediaCollection($validated['collection_name']);
            }
            if ($model) {
                foreach ($validated['files'] as $file) {
                    if (!$model->addMedia($file)->toMediaCollection($validated['collection_name'])) {
                        return response()->json(['message' => 'An error occurred.'], 500);
                    }
                }   
                return response()->json(['message' => 'Files uploaded successfully.']);
            }
        }   

        return response()->json(['message' => 'An error occurred.'], 500);
    }

    public function get_files(Request $request)
    {
        // Get files for the authenticated user
        $user = auth()->user();
        
        // Debug: Check what's in the media table
        $allMediaInDb = Media::all(['id', 'model_type', 'model_id', 'collection_name', 'name']);
        $userMediaInDb = Media::where('model_type', 'App\\Models\\User')
                              ->where('model_id', $user->id)
                              ->get(['id', 'model_type', 'model_id', 'collection_name', 'name']);
        
        // Since the relationship is broken, use direct database query
        $userFiles = Media::where('model_type', 'App\\Models\\User')
                          ->where('model_id', $user->id)
                          ->get();
        
        // Get unique collection names for debugging
        $collections = $userFiles->pluck('collection_name')->unique()->values();
        
        // Transform the files to include useful information
        $transformedFiles = $userFiles->map(function ($media) {
            return [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'collection_name' => $media->collection_name,
                'url' => $media->getUrl(),
                'created_at' => $media->created_at,
                'temp' => $media->temp ?? false
            ];
        })->values();
        
        return response()->json([
            'message' => 'Files retrieved successfully.',
            'data' => $transformedFiles,
            'total' => $transformedFiles->count(),
            'debug' => [
                'authenticated_user_id' => $user->id,
                'authenticated_user_email' => $user->email,
                'user_media_count_via_direct_query' => $userFiles->count(),
                'available_collections' => $collections,
                'fixed_relationship_issue' => true
            ]
        ]);
    }

    public function get_all_files(Request $request)
    {
        // Get ALL files for the authenticated user (for debugging)
        $user = auth()->user();
        $allFiles = $user->getMedia();
        
        $transformedFiles = $allFiles->map(function ($media) {
            return [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'collection_name' => $media->collection_name,
                'url' => $media->getUrl(),
                'temp' => $media->temp ?? false
            ];
        });
        
        return response()->json([
            'message' => 'All files retrieved successfully.',
            'data' => $transformedFiles,
            'total' => $transformedFiles->count()
        ]);
    }
}
