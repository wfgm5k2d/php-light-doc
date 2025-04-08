<?php

namespace Wfgm5k2d\PhpLightDoc\Http\Controllers;

class PhpLightDocController extends Controller
{
    public function index()
    {
        $jsonFile = config('php-light-doc.path.file-documentation');
        $documentation = json_decode(file_get_contents($jsonFile), true);

        return view('php-light-doc::documentation', [
            'documentation' => $documentation
        ]);
    }
}
