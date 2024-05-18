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

        //Latest
        $userId = auth()->user()->id;
        $training = new Training();

        $this->validate($request, [
            'photo' => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx|max:10000',
        ]);

        if ($request->hasFile('photo')) {
            $uploadedFile = $request->file('photo');
            $originalExtension = $uploadedFile->getClientOriginalExtension();
            $filename = 'training_' . $userId . '_' . time();
            $imageFilename = $filename . '.jpg';

            // Ensure the directory exists
            $path = public_path('images/trainings');
            if (!File::isDirectory($path)) {
                File::makeDirectory($path, 0777, true, true);
            }

            if (in_array($originalExtension, ['doc', 'docx'])) {
                // Convert Word document to PDF using LibreOffice
                $tempDir = storage_path('app/temp');
                $command = "\"C:\\Program Files\\LibreOffice\\program\\soffice\" --headless --convert-to pdf --outdir " . escapeshellarg($tempDir) . " " . escapeshellarg($uploadedFile->getRealPath());
                \Log::info("Executing command: $command");

                // List files before conversion
                $beforeFiles = File::files($tempDir);

                $output = null;
                $returnVar = null;
                exec($command, $output, $returnVar);

                \Log::info("Command output: " . implode("\n", $output));
                \Log::info("Command return status: $returnVar");

                if ($returnVar !== 0) {
                    \Log::error("Failed to convert Word document to PDF: " . implode("\n", $output));
                    return redirect()->back()->withErrors(['photo' => 'Failed to convert Word document to PDF.']);
                }

                // List files after conversion
                $afterFiles = File::files($tempDir);

                // Find the new file by comparing before and after files
                $newFiles = array_diff($afterFiles, $beforeFiles);
                if (count($newFiles) !== 1) {
                    \Log::error("Failed to determine the converted PDF file.");
                    return redirect()->back()->withErrors(['photo' => 'Failed to determine the converted PDF file.']);
                }

                $pdfPath = reset($newFiles)->getRealPath();

                // Verify the generated PDF
                if (!File::exists($pdfPath)) {
                    \Log::error("Generated PDF file does not exist: " . $pdfPath);
                    return redirect()->back()->withErrors(['photo' => 'Generated PDF file does not exist.']);
                }

                if (!is_readable($pdfPath)) {
                    \Log::error("Generated PDF file is not readable: " . $pdfPath);
                    return redirect()->back()->withErrors(['photo' => 'Generated PDF file is not readable.']);
                }

                // Convert the generated PDF to an image
                $imagePath = $path . '/' . $imageFilename;

                try {
                    $pdf = new Pdf($pdfPath);
                    $pdf->saveImage($imagePath);
                } catch (\Exception $e) {
                    \Log::error("Failed to convert PDF to image1: " . $e->getMessage());
                    \Log::error("Exception trace: " . $e->getTraceAsString());
                    return redirect()->back()->withErrors(['photo' => 'Failed to convert PDF to image1.']);
                }

                // Delete the temporary PDF file
                unlink($pdfPath);
            } elseif ($originalExtension === 'pdf') {
                // Convert PDF to image directly
                try {
                    $pdf = new Pdf($uploadedFile->getRealPath());
                    $pdf->saveImage($path . '/' . $imageFilename);
                } catch (\Exception $e) {
                    \Log::error("Failed to convert PDF to image2: " . $e->getMessage());
                    \Log::error("Exception trace: " . $e->getTraceAsString());
                    return redirect()->back()->withErrors(['photo' => 'Failed to convert PDF to image2.']);
                }
            } else {
                // If it's not a PDF or Word document, directly save the uploaded image
                $uploadedFile->move($path, $imageFilename);
            }

            $training->photo = $imageFilename;
        }

        $training->save();

        session()->flash('message', 'Display document');

        return redirect()->route('user.training-list');



        //LAST CODE UPDATE
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

        //     if ($uploadedFile->getClientOriginalExtension() === 'pdf') {
        //         // Convert PDF to image
        //         $pdf = new Pdf($uploadedFile->getRealPath());
        //         $pdf->saveImage($path . '/' . $filename);
        //     } else {
        //         // If it's not a PDF, directly save the uploaded image
        //         $uploadedFile->move($path, $filename);
        //     }

        //     $training->photo = $filename;
        // }

        // $training->save();

        // session()->flash('message', 'Display document');

        // return redirect()->route('user.training-list');

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