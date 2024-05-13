<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Image;

class PrintJobController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function displayExplain()
    {
        phpinfo();
        return view('user.print-explain');
    }

    public function displayUpload()
    {

        return view('user.print-upload');
    }

    public function displayPreference()
    {

        return view('user.print-preference');
    }

    public function displayPreview()
    {

        return view('user.print-preview');
    }

    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
