<?php

namespace App\Http\Controllers;

use App\Models\Layanan;

class LayananController extends Controller
{
    public function index()
    {
        $layanans = Layanan::where('aktif', true)
            ->with('persyaratans')
            ->orderBy('id')
            ->get();

        return view('layanan.index', compact('layanans'));
    }

    public function show(Layanan $layanan)
    {
        abort_if(!$layanan->aktif, 404);

        $layanan->load('persyaratans');

        return view('layanan.show', compact('layanan'));
    }
}
