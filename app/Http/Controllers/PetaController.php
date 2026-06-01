<?php

namespace App\Http\Controllers;

class PetaController extends Controller
{
    public function index()
    {
        return view('frontend.pages.peta-v2', ['documents' => []]);
    }
}
