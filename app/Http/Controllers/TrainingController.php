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
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;


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
            $pdfFilename = $filename . '.pdf';
            $tempPath = storage_path('app/temp');

            // Ensure the directory exists
            $path = public_path('images/trainings');
            if (!File::isDirectory($path)) {
                File::makeDirectory($path, 0777, true, true);
            }

            // Clear temporary directory before conversion
            $tempPath = storage_path('app/temp');
            File::cleanDirectory($tempPath);

            if (in_array($originalExtension, ['doc', 'docx'])) {
                // Convert Word document to PDF using LibreOffice
                $command = "\"C:\\Program Files\\LibreOffice\\program\\soffice\" --headless --convert-to pdf --outdir " . escapeshellarg($tempPath) . " " . escapeshellarg($uploadedFile->getRealPath());
                \Log::info("Executing command: $command");

                // List files in tempPath before conversion
                $beforeFiles = scandir($tempPath);
                \Log::info("Files before conversion: " . implode(", ", $beforeFiles));

                $output = null;
                $returnVar = null;
                exec($command, $output, $returnVar);

                \Log::info("Command output: " . implode("\n", $output));
                \Log::info("Command return status: $returnVar");

                // List files in tempPath after conversion
                $afterFiles = scandir($tempPath);
                \Log::info("Files after conversion: " . implode(", ", $afterFiles));

                if ($returnVar !== 0) {
                    \Log::error("Failed to convert Word document to PDF: " . implode("\n", $output));
                    return redirect()->back()->withErrors(['photo' => 'Failed to convert Word document to PDF.']);
                }

                // Find the newly generated PDF file by comparing the file lists before and after conversion
                $newFiles = array_diff($afterFiles, $beforeFiles);
                $pdfPath = null;
                foreach ($newFiles as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                        $pdfPath = $tempPath . '/' . $file;
                        break;
                    }
                }

                if (!$pdfPath || !file_exists($pdfPath)) {
                    \Log::error("Generated PDF file does not exist or is not readable: $pdfPath");
                    return redirect()->back()->withErrors(['photo' => 'Generated PDF file does not exist or is not readable.']);
                }

                // Log the existence of the PDF file
                \Log::info("Generated PDF file exists: $pdfPath");
            } elseif ($originalExtension === 'pdf') {
                // Directly use the uploaded PDF
                $pdfPath = $uploadedFile->getRealPath();
                \Log::info("Using uploaded PDF: $pdfPath");
            } else {
                // If it's not a PDF or Word document, directly save the uploaded image
                $imageFilename = $filename . '.' . $originalExtension;
                $uploadedFile->move($path, $imageFilename);
                $training->photo = $imageFilename;
                $training->save();
                session()->flash('message', 'Display document');
                return redirect()->route('user.training-list');
            }

            // Convert the generated or uploaded PDF to images
            try {
                $pdf = new Pdf($pdfPath);
            } catch (\Exception $e) {
                \Log::error("Failed to initialize Pdf object: " . $e->getMessage());
                return redirect()->back()->withErrors(['photo' => 'Failed to initialize Pdf object.']);
            }

            $pageCount = $pdf->getNumberOfPages();
            \Log::info("Number of pages in PDF: $pageCount");
            for ($page = 1; $page <= $pageCount; $page++) {
                $imageFilename = $filename . '_page' . $page . '.jpg';
                $imagePath = $path . '/' . $imageFilename;

                try {
                    $pdf->setPage($page)->saveImage($imagePath);
                    \Log::info("Saved image for page $page: $imagePath");
                } catch (\Exception $e) {
                    \Log::error("Failed to convert PDF page $page to image: " . $e->getMessage());
                    return redirect()->back()->withErrors(['photo' => "Failed to convert PDF page $page to image."]);
                }

                // Save each page as a separate Training entry (or handle as needed)
                $trainingPage = new Training();
                $trainingPage->photo = $imageFilename;
                $trainingPage->save();

                // Store the training ID in the session for potential deletion
                $uploadedTrainingIds = Session::get('uploaded_training_ids', []);
                $uploadedTrainingIds[] = $trainingPage->id;
                Session::put('uploaded_training_ids', $uploadedTrainingIds);
            }

            // Delete the temporary PDF file if it was converted from a Word document
            if (in_array($originalExtension, ['doc', 'docx'])) {
                unlink($pdfPath);
            }
        }

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
    public function deleteTraining(Request $request)
    {
        // Fetch the training IDs stored in the session
        $trainingIds = Session::get('uploaded_training_ids', []);
        Log::info("Deleting training IDs: " . implode(", ", $trainingIds));

        foreach ($trainingIds as $trainingId) {
            $training = Training::find($trainingId);

            if ($training) {
                // Ensure the filename pattern is retrieved correctly
                $filenamePattern = $training->photo;
                $directory = public_path('images/trainings');

                // Delete the file
                $file = $directory . '/' . $filenamePattern;
                if (File::exists($file)) {
                    File::delete($file);
                }

                // Delete the record from the database
                $training->delete();
            }
        }

        // Clear the session data related to the upload
        Session::forget('uploaded_training_ids');

        return redirect()->route('user.training-form')->with('message', 'All uploaded files and data have been deleted.');
    }



}