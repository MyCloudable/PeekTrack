<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\File;
use App\Models\PO;
class FileUpload extends Controller
{
  public function createForm(){
    return view('file-upload');
  }
 
  public function fileUpload(Request $req){
        $req->validate([
        'file' => 'required|mimes:jpg,png,jpeg,csv,txt,xlx,xls,pdf|max:100000'
        ]);
        $fileModel = new File;
        if($req->file()) {
			
            $fileName = $req->jobnumber.'_'.time().'_'.$req->file->getClientOriginalName();
			$fileName = str_replace(' ', '', $fileName);
            $filePath = $req->file('file')->storeAs('uploads', $fileName, 'public');
			$fileModel->job_number = $req->jobnumber;
            $fileModel->name = $fileName;
			$fileModel->description = $req->docdesc;
			$fileModel->type = $req->doctype;
			$fileModel->doctype = 0;
            $fileModel->file_path = '/storage/public/' . $filePath;
            $fileModel->save();
            return back()
            ->with('successfile','File has been uploaded.')
            ->with('file', $fileName);
        }

   }
   
     public function jobcardUpload(Request $req){
        $req->validate([
        'file' => 'required|mimes:jpg,png,jpeg,csv,txt,xlx,xls,pdf|max:100000'
        ]);
        $fileModel = new File;
        if($req->file()) {
            $fileName = $req->jobnumber.'_'.$req->link.'_'.time().'_'.$req->file->getClientOriginalName();
			$fileName = str_replace(' ', '', $fileName);
            $filePath = $req->file('file')->storeAs('uploads', $fileName, 'public');
			$fileModel->job_number = $req->jobnumber;
            $fileModel->name = $fileName;
			$fileModel->description = $req->docdesc;
			$fileModel->type = $req->doctype;
			$fileModel->doctype = 1;
			$fileModel->link = $req->link;
            $fileModel->file_path = '/storage/public/' . $filePath;
            $fileModel->save();
            return back()
            ->with('successfile','File has been uploaded.')
            ->with('file', $fileName);
        }

   }

        public function uploadpo(Request $req){

			$poModel = new PO();
			$poModel->job_number = $req->job_number;
            $poModel->userId = $req->userId;
			$poModel->po_number = $req->po_number;
			$poModel->signer_name = $req->signer_name;
			$poModel->signature = $req->signature;
			$poModel->notes = $req->notes;
			$poModel->link = $req->link;
            $poModel->save();
			
   }
   
   public function fileDelete(Request $req){
	   
	   $fileId = $req->id;
	   $file = File::find($fileId);
	    if (!$file) {
        return back()->with('error', 'File not found.');
    }

    // Set the 'deleted' field to 1
    $file->active = 1;
    $file->save();

    return back()->with('successfile', 'File has been archived.');
   }
}