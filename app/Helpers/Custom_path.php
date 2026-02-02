<?php

// untuk akses upload file
if (!function_exists('asset_upload')) {
    function asset_upload($path)
    {
        return asset("uploads/{$path}");
    }
}

// untuk lokasi upload file
if (!function_exists('upload_path')) {
    function upload_path($path)
    {
        return public_path("uploads/{$path}");
    }
}

// untuk lokasi format file
if (!function_exists('format_path')) {
    function format_path($path)
    {
        return public_path("formats/{$path}");
    }
}
