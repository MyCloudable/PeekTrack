<?php
  
namespace App\Http\Controllers;
use App\Models\Production;
use Illuminate\Http\Request;

  
class SearchController extends Controller
{
    public function autocomplete(Request $request)
    {
        $term = $request->input('term');

        // Perform your search based on the term
        $results = YourModel::where('column_name', 'LIKE', '%' . $term . '%')->pluck('column_name');

        return response()->json($results);
    }
}