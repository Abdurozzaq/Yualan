<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function showProductFile($folder, $filename)
    {
        $path = "public/products/$folder/$filename";
        if (Storage::disk('local')->exists($path)) {
            return response()->file(storage_path("app/private/$path"));
        }
        abort(404);
    }
}
