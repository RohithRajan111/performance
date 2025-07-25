<?php

namespace App\Http\Controllers;

use App\Models\CalendarNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarNoteController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'note' => 'required|string|max:1000',
        ]);

        CalendarNote::create([
            'user_id' => Auth::id(),
            'date' => $request->date,
            'note' => $request->note,
        ]);

        return redirect()->back()->with('success', 'Note added successfully.');
    }

    /**
     * Update the specified calendar note in storage.
     */
    public function update(Request $request, CalendarNote $calendarNote)
    {
        // Ensure the user can only update their own notes
        abort_if($calendarNote->user_id !== Auth::id(), 403);

        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $calendarNote->update(['note' => $request->note]);

        return redirect()->back()->with('success', 'Note updated successfully.');
    }

    /**
     * Remove the specified calendar note from storage.
     */
    public function destroy(CalendarNote $calendarNote)
    {
        // Ensure the user can only delete their own notes
        abort_if($calendarNote->user_id !== Auth::id(), 403);

        $calendarNote->delete();

        return redirect()->back()->with('success', 'Note deleted successfully.');
    }
}
