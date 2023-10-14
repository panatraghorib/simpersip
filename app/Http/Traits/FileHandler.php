<?php

namespace App\Http\Traits;
use Log;
use Exception;
use Webpatser\Uuid\Uuid;
use Illuminate\Support\Facades\Storage;

trait FileHandler
{

    public function handlePath($file)
    {
        $disk = config('appstra.storage.disk');
        $path = '';

        if (!is_null($file) && $file !== '') {
            $fileExists = Storage::disk($disk)->exists($file);
            if ($fileExists) {
                if ($disk != 'public' && $disk != 'local') {
                    $path = Storage::disk($disk)->url($file);
                } else {
                    $path = Storage::disk($disk)->path($file);
                }
            }
        }

        return $path;
    }

    public function handleView($file)
    {
        try {
            $path = $this->handlePath($file);
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $headers = get_headers($path, 1);
                $type = $headers['Content-Type'];
                header("Content-type:$type");
                ob_clean();
                readfile($path);

                return;
            }
            $mime = mime_content_type($path);
            header("Content-type:$mime");
            ob_clean();
            readfile($path);

        } catch (Exception $e) {
            Log::debug($e->getMessage());
            $path = public_path('badaso-images/badaso.png');
            $mime = mime_content_type($path);
            header("Content-type:$mime");
            ob_clean();
            readfile($path);

        }
    }

    public function handleDownload($file)
    {
        return Storage::disk(config('appstra.storage.disk', 'public'))->download($file);
    }

    public function handleDeleteFile($file)
    {
        return Storage::disk(config('appstra.storage.disk', 'public'))->delete($file);
    }

    public function handleUpload($files, $data_type = null, $custom_path = null)
    {
        $path_List = [];
        foreach ($files as $file) {
            $uuid = Uuid::generate(4);
            if (is_array($file) && array_key_exists('base64', $file)) {
                $encoded_file = $file['base64'];
                $decoded_file = base64_decode(explode(',', $encoded_file)[1]);
                $filename = '';
                if (array_key_exists('name', $file)) {
                    $file['name'] = str_replace(' ', '_', $file['name']);
                    $filename = $uuid.'-'.$file['name'];
                }
                $filepath = 'uploads/';
                if (! is_null($data_type)) {
                    $filepath .= $data_type->slug.'/';
                }

                if (! is_null($custom_path)) {
                    $filepath .= $custom_path.'/';
                }

                Storage::disk(config('appstra.storage.disk', 'public'))->put($filepath.$filename, $decoded_file);

                $path_List[] = $filepath.$filename;
            } else {
                $path_List[] = $file;
            }
        }

        return $path_List;
    }
}
