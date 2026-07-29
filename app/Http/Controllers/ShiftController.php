<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::withCount('users')->get();
        return view('shifts.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        Shift::create([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return redirect()->route('shifts.index')->with('success', 'Shift created successfully.');
    }

    public function update(Request $request, Shift $shift)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $shift->update([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return redirect()->route('shifts.index')->with('success', 'Shift updated successfully.');
    }

    public function destroy(Shift $shift)
    {
        if ($shift->users()->count() > 0) {
            return redirect()->route('shifts.index')->with('error', 'Cannot delete shift assigned to users.');
        }

        $shift->delete();
        return redirect()->route('shifts.index')->with('success', 'Shift deleted successfully.');
    }
}
