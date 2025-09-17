<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role; // ⬅️ add this
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserAdminController extends Controller
{
    public function index()
    {
        $roles = Role::query()
            ->selectRaw('id as value, name as label')
            ->orderBy('id')
            ->get();

        return view('admin.users.index', compact('roles'));
    }

    public function list(Request $request)
    {
        $search = $request->input('search.value');
        $query  = User::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name','like',"%{$search}%")
                  ->orWhere('email','like',"%{$search}%");
            });
        }

        $total     = $query->count();

        $columns = ['id','name','email','role_id','location','class','pay_rate','created_at'];
        $orderColIndex = (int)($request->input('order.0.column', 0));
        $orderDir      = $request->input('order.0.dir', 'asc');
        $orderCol      = $columns[$orderColIndex] ?? 'id';

        $start  = (int)$request->input('start', 0);
        $length = (int)($request->input('length', 10) ?: 10);

        $data = $query->orderBy($orderCol, $orderDir)
                      ->skip($start)
                      ->take($length)
                      ->get()
                      ->map(function($u){
                          return [
                              'id'         => $u->id,
                              'name'       => $u->name,
                              'email' => $u->email ?? '',
                              'role_id'    => $u->role_id,
                              'location'   => $u->location,
                              'class'      => $u->class,
                              'pay_rate'   => $u->pay_rate,
                              'created_at' => optional($u->created_at)->format('Y-m-d'),
                          ];
                      });

        return response()->json([
            'draw'            => (int)$request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    public function show(User $user)
    {
        return response()->json([
            'id'      => $user->id,
            'name'    => $user->name,
            'email'   => $user->email ?? '',
            'role_id' => $user->role_id,
            'location'  => $user->location,
            'class'     => $user->class,
            'pay_rate'  => $user->pay_rate,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'                  => ['required','string','max:255'],
            'role_id'               => ['required','integer'],
            'location'     => ['nullable','regex:/^\d{2}$/'], // exactly 2 digits
            'class'        => ['nullable','regex:/^\d{2}$/'], // exactly 2 digits
            'pay_rate'     => ['nullable','in:0,1'],          // 0/1
            'new_password'          => ['nullable','string','min:8','confirmed'],
        ]);

        $user->name    = $validated['name'];
        $user->role_id = (int)$validated['role_id'];

        
        if ($request->has('location'))  { $user->location = $validated['location']; }
        if ($request->has('class'))     { $user->class    = $validated['class']; }
        if ($request->has('pay_rate'))  { $user->pay_rate = (int)$validated['pay_rate']; }

        if (!empty($validated['new_password'])) {
            $user->password = $validated['new_password'];
        }

        $user->save();

        return response()->json(['success' => true, 'message' => 'User updated']);
    }
}
