<?php

namespace App\Controllers;

class Legal extends BaseController
{
    public function privacy()
    {
        return view('legal/privacy');
    }

    public function tos()
    {
        return view('legal/tos');
    }
}
