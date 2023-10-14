<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use UniSharp\LaravelFilemanager\Controllers\DeleteController;
use UniSharp\LaravelFilemanager\Controllers\ItemsController;
use UniSharp\LaravelFilemanager\Controllers\UploadController;

class FileController extends Controller
{

    public function uploadFile(Request $request)
    {
        $files = $request->input('files', []);
        return $this->handleUpload($files);
    }

    public function downloadFile(Request $request)
    {
        $files = $request->input('files', []);
        return $this->handleDownload($files);
    }

    public function deleteFile(Request $request)
    {
        $files = $request->input('files', []);
        return $this->handleDelete($files);
    }

    public function viewFile(Request $request)
    {
        $files = $request->input('files', []);
        return $this->handleView($files);
    }

    public function browseFileUsingLfm()
    {
        $items = new ItemsController();
        $files = $items->getItems();

        return ResponseApi::success(json_decode(json_encode($files)));
    }

    public function uploadFileUsingLfm(Request $request)
    {
        $upload = new UploadController();
        $file = $upload->upload();

        if (key_exists('error', $file->original)) {
            return ResponseApi::failed($file);
        }

        return ResponseApi::success(json_decode(
            json_encode($file))
        );
    }

    public function deleteFileUsingLfm(Request $request)
    {
        $delete = new DeleteController();
        $file = $delete->getDelete();

        return ResponseApi::success(json_decode(json_encode($file)));
    }

    public function availableMimetype()
    {
        return ResponseApi::success(config('lfm.folder_categories'));
    }

}
