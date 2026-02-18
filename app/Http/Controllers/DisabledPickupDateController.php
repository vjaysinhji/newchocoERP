<?php

namespace App\Http\Controllers;

use App\Models\DisabledPickupDate;
use Illuminate\Http\Request;

class DisabledPickupDateController extends Controller
{
    public function index()
    {
        $pickupDates = DisabledPickupDate::orderBy('date')->orderBy('sort_order')->get();
        return view('backend.delivery.pickup-date.index', compact('pickupDates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'reason_en' => 'nullable|string|max:255',
            'reason_ar' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'type_name' => 'required|in:web,pos,both',
        ]);
        $data['is_active'] = true;
        DisabledPickupDate::create($data);
        return redirect()->back()->with('message', 'Pickup date disabled successfully');
    }

    public function update(Request $request, $id)
    {
        $row = DisabledPickupDate::findOrFail($id);
        $data = $request->validate([
            'date' => 'required|date',
            'reason_en' => 'nullable|string|max:255',
            'reason_ar' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'type_name' => 'required|in:web,pos,both',
            'is_active' => 'required|boolean',
        ]);
        $row->update($data);
        return redirect()->back()->with('message', 'Pickup date updated successfully');
    }

    public function destroy($id)
    {
        $row = DisabledPickupDate::findOrFail($id);
        $row->delete();
        return redirect()->back()->with('message', 'Pickup date deleted successfully');
    }

    public function inlineStatus(Request $request, $id)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $row = DisabledPickupDate::findOrFail($id);
        $row->is_active = $request->boolean('is_active');
        $row->save();
        return response()->json(['success' => true]);
    }

    public function inlineSort(Request $request, $id)
    {
        $request->validate(['sort_order' => 'nullable|integer']);
        $row = DisabledPickupDate::findOrFail($id);
        $row->sort_order = $request->input('sort_order');
        $row->save();
        return response()->json(['success' => true]);
    }
}
