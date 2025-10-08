<?php

namespace App\Traits;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait UploadToLocalTrait
{
    use LoggableTrait;

    public function uploadToLocal($file, $directory = 'uploads')
    {
        try {
            if (!$file->isValid()) {
                return response()->json([
                    'message' => 'Invalid file',
                ], Response::HTTP_BAD_REQUEST);
            }

            $path = $file->store($directory, 'public');

            return $path;
        } catch (\Exception $e) {
            $this->logError($e);

            return response()->json([
                'message' => 'Có lỗi xảy ra, vui lòng thử lại sau',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function uploadMultiple($files, $directory = 'uploads')
    {
        try {
            $uploadedFiles = [];

            foreach ($files as $file) {
                if (!$file->isValid()) {
                    continue;
                }

                $filePath = Storage::put($directory, $file);

                if ($filePath) {
                    $uploadedFiles[] = $filePath;
                }
            }

            if (empty($uploadedFiles)) {
                return response()->json([
                    'message' => 'No valid files were uploaded',
                ], Response::HTTP_BAD_REQUEST);
            }

            return $uploadedFiles;
        } catch (\Exception $e) {
            $this->logError($e);

            return response()->json([
                'message' => 'Có lỗi xảy ra, vui lòng thử lại sau',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteFromLocal($filePath, $directory = 'uploads')
    {
        try {
            if (Storage::exists($filePath)) {
                return Storage::delete($filePath);
            }

            return response()->json([
                'message' => 'File not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logError($e);

            return response()->json([
                'message' => 'Có lỗi xảy ra, vui lòng thử lại sau',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
