<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Training;
use Illuminate\Support\Facades\File;
// use Image;
use App\Models\UploadedFile;
use Illuminate\Support\Facades\Storage;
// use Intervention\Image\ImageManagerStatic as Image;
use Spatie\PdfToImage\Pdf;
use Intervention\Image\Facades\Image;



class TrainingController extends Controller
{

    public function getTrainingList()
    {
        $trainings = Training::all();
        // $uploaded_files = UploadedFile::all();
        return view('user.training-list', compact('trainings'));

    }

    public function getTrainingForm()
    {

        return view('user.training-form');
    }

    public function postAddTraining(Request $request): RedirectResponse
    {

        // $userId = auth()->user()->id;
        // $training = new Training();

        // $this->validate($request, [
        //     'photo' => 'nullable|mimes:pdf,jpg,jpeg,png|max:10000',
        // ]);

        // if ($request->hasFile('photo')) {
        //     $uploadedFile = $request->file('photo');
        //     $filename = 'training_' . $userId . '_' . time() . '.jpg'; // Assuming you want to save the converted image as JPG

        //     // Ensure the directory exists
        //     $path = public_path('images/trainings');
        //     if (!File::isDirectory($path)) {
        //         File::makeDirectory($path, 0777, true, true);
        //     }

        //     $filePath = $uploadedFile->getRealPath();
        //     $extension = $uploadedFile->getClientOriginalExtension();

        //     try {
        //         if ($extension === 'pdf') {
        //             // Convert PDF to image
        //             $pdf = new Pdf($filePath);
        //             $pdf->setOutputFormat('jpg'); // Ensure the output format is set
        //             $pdf->saveImage($path . '/' . $filename);
        //         } else {
        //             // If it's not a PDF, directly save the uploaded image
        //             $image = Image::make($filePath)->encode('jpg');
        //             $image->save($path . '/' . $filename);
        //         }

        //         $training->photo = $filename;
        //     } catch (\Exception $e) {
        //         return redirect()->back()->withErrors(['error' => 'Failed to process the file: ' . $e->getMessage()]);
        //     }
        // }

        // $training->save();

        // session()->flash('message', 'Display document');

        // return redirect()->route('user.training-list');



        //LAST CODE UPDATE
        $userId = auth()->user()->id;
        $training = new Training();


        $this->validate($request, [
            'photo' => 'nullable|mimes:pdf,jpg,jpeg,png|max:10000',
        ]);

        if ($request->hasFile('photo')) {
            $uploadedFile = $request->file('photo');
            $filename = 'training_' . $userId . '_' . time() . '.jpg'; // Assuming you want to save the converted image as JPG

            // Ensure the directory exists
            $path = public_path('images/trainings');
            if (!File::isDirectory($path)) {
                File::makeDirectory($path, 0777, true, true);
            }

            if ($uploadedFile->getClientOriginalExtension() === 'pdf') {
                // Convert PDF to image
                $pdf = new Pdf($uploadedFile->getRealPath());
                $pdf->saveImage($path . '/' . $filename);
            } else {
                // If it's not a PDF, directly save the uploaded image
                $uploadedFile->move($path, $filename);
            }

            $training->photo = $filename;
        }

        $training->save();

        session()->flash('message', 'Display document');

        return redirect()->route('user.training-list');

        // // Validate the incoming file. Refuses anything bigger than 2048 kilobyes (=2MB)
        // $request->validate([
        //     'file_upload' => 'required|mimes:pdf,jpg,png|max:2048',
        // ]);

        // // Store the file in storage\app\public folder
        // $file = $request->file('file_upload');
        // $fileName = $file->getClientOriginalName();
        // $filePath = $file->store('images', 'public');

        // // Store file information in the database
        // $uploadedFile = new UploadedFile();
        // // $uploadedFile->filename = $fileName;
        // // $uploadedFile->original_name = $file->getClientOriginalName();
        // // $uploadedFile->file_path = $filePath;
        // // $uploadedFile->save();

        // // Redirect back to the index page with a success message
        // // return redirect()->route('user.training-list')
        // //     ->with('success', "File `{$uploadedFile->original_name}` uploaded successfully.");

        // $userId = auth()->user()->id;
        // $training = new Training();

        // $this->validate($request, [
        //     'photo' => 'nullable|mimes:pdf,jpg,jpeg,png|max:10000',
        // ]);

        // //save photo
        // if ($request->hasFile('photo')) {
        //     $uploadedFile = $request->file('photo');
        //     $filename = 'training_' . $userId . '_' . time() . '.' . $uploadedFile->getClientOriginalExtension();

        //     // Ensure the directory exists
        //     $path = public_path('images/trainings');
        //     if (!File::isDirectory($path)) {
        //         File::makeDirectory($path, 0777, true, true);
        //     }

        //     UploadedFile::make($uploadedFile)->resize(1080, 1920)->save($path . '/' . $filename);
        //     $training->photo = $filename;

        // }
        // $training->save();

        // Session()->flash('message', 'Display document');

        // return redirect()->route('user.training-list');

    }



}