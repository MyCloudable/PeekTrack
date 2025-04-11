<?php

namespace App\Http\Requests\Clock;

use App\Models\Crew;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreCrewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {

        return [
            'crew_type_id' => 'required',
            'superintendentId' => 'required',
            'managerId' => 'required',
            'crew_members' => ['sometimes', function ($attribute, $value, $fail) {

                $crewId = $this->route('crew') ? $this->route('crew')->id : ''; // crew id for update request


                // Fetch existing crew members' IDs from the database
                $existingCrews = \DB::table('crews')
                ->where('id', '!=', $crewId) // Exclude the current crew
                ->whereNotNull('crew_members')
                ->get(['crew_members']);

                // Get all crew member names for the new crew members
                $memberNames = \DB::table('users')->whereIn('id', $value)->pluck('name', 'id')->toArray();

                $conflictingMembers = [];
    
                foreach ($existingCrews as $crew) {
                    $members = json_decode($crew->crew_members, true); // Decode the JSON string to an array
    
                    // Check for overlapping members
                    $overlaps = array_intersect($value, $members);
    
                    if ($overlaps) {
                        foreach ($overlaps as $id) {
                            if (isset($memberNames[$id])) {
                                $conflictingMembers[] = $memberNames[$id];
                            }
                        }
                    }
                }
    
                if (!empty($conflictingMembers)) {
									
					
	
					
				
                    $fail('The following crew members are already part of another crew: ' . implode(', ', $conflictingMembers));
                }
            }],
        ];

    }



//     public function rules()
//     {
//         return [
//             'crew_type_id' => 'required|exists:crew_types,id',
//             'superintendentId' => 'required|exists:users,id',
//             'crew_members' => 'required|array',
//             'crew_members.*' => 'exists:users,id'
//         ];
//     }


//     public function withValidator($validator)
//     {
//         $validator->after(function ($validator) {
//             $crewId = $this->route('crew'); // Get the crew ID from the route if it's an update request
//             $crewMembers = $this->input('crew_members', []);

//             $overlappingMembers = $this->checkForOverlappingCrewMembers($crewId, $crewMembers);

//             if (!empty($overlappingMembers)) {
//                 $validator->errors()->add('crew_members', 'These crew members are already part of another crew: ' . implode(', ', $overlappingMembers));
//             }
//         });
//     }


//     protected function checkForOverlappingCrewMembers($crewId, $crewMembers)
// {
//     $existingCrews = Crew::query()
//         ->when($crewId, function ($query) use ($crewId) {
//             return $query->where('id', '!=', $crewId);
//         })
//         ->get();

//     $overlappingMembers = [];

//     foreach ($existingCrews as $crew) {
//         // Handle both string and array cases
//         $existingMembers = is_string($crew->crew_members) 
//             ? json_decode($crew->crew_members, true) 
//             : $crew->crew_members;

//         foreach ($crewMembers as $memberId) {
//             if (in_array($memberId, $existingMembers)) {
//                 $overlappingMembers[] = User::find($memberId)->name; // Get the name of the overlapping member
//             }
//         }
//     }

//     return array_unique($overlappingMembers);
// }


}
