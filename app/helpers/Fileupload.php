<?php

use Illuminate\Http\Request;
//Single file upload function

function file_upload(Request $request)
{
    //Get file
    $file = $request->file('profile_img');
    if ($file) {
        $rdm = uniqid();
        $path = storage_path() . "/app/file";
        $name = '/app/file/' . $rdm . '-' . $file->getClientOriginalName();
        $res = $file->move($path, $name);
        if ($res) {
            return $name;
        }
    } else {
        echo "error";
    }
}
/*
Multiple file upload function
 */
function UploadMultipleFile(Request $request)
{
    echo "string"; die();
    if ($request->hasFile('file')) {
        //Take file extention
        $allowedfileExtension = ['jpg', 'png', 'jpeg', 'mpeg', 'ogg', 'mp4', 'webm', '3gp', 'mov', 'flv', 'avi', 'wmv', 'ts'];
        $files = $request->file('file');

        foreach ($files as $file) {
            $extension = $file->getClientOriginalExtension();
            //Check file extention is valid or not
            $check = in_array($extension, $allowedfileExtension);

            if ($check) {

                foreach ($request->file as $mediaFiles) {
                    $ext = $mediaFiles->getClientOriginalExtension();
                    $media_ext = $mediaFiles->getClientOriginalName();
                    $media_no_ext = pathinfo($media_ext, PATHINFO_FILENAME);
                    $path = storage_path() . "/app/ad_images";
                    $mFiles = '/app/ad_images/' . uniqid() . '.' . $ext;
                    $mediaFiles->move($path, $mFiles);
                    $filename[] = $mFiles;
                }
                return $filename;

            } else {

                $error = ['error' => 'Invalid file format'];
                return response()->json($error, JsonResponse::HTTP_BAD_REQUEST);
            }
        }
    } else {
        return 'no-image';
    }

}

function LicenceFront(Request $request)
{
    //Get file
    $file = $request->file('idFront');
    if ($file) {
        $rdm = uniqid();
        $path = storage_path() . "/app/licence";
        $name = $rdm . '-' . $file->getClientOriginalName();
        $res = $file->move($path, $name);
        if ($res) {
            return '/app/licence/' . $name;
        }
    }
}

function LicenceBack(Request $request)
{
    //Get file
    $file = $request->file('idBack');
    if ($file) {
        $rdm = uniqid();
        $path = storage_path() . "/app/licence";
        $name = $rdm . '-' . $file->getClientOriginalName();
        $res = $file->move($path, $name);
        if ($res) {
            return '/app/licence/' . $name;
        }
    }
}

function CategoryFile(Request $request)
{
    //Get file
    $file = $request->file('file');
    if ($file) {
        $rdm = uniqid();
        $path = storage_path() . "/app/file";
        $name = $rdm . '-' . $file->getClientOriginalName();
        $res = $file->move($path, $name);
        if ($res) {
            return '/app/file/' . $name;
        }
    }
}
