<?php

namespace App\Http\Controllers;

use App\Models\PickupTime;
use Illuminate\Http\Request;

class PickupTimeController extends Controller
{
    public function index()
    {
        $times = PickupTime::orderBy('sort_order')->orderBy('from_time')->get();
        return view('backend.delivery.pickup-timing.index', compact('times'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_time' => 'required|string|max:20',
            'to_time' => 'required|string|max:20',
            'sort_order' => 'nullable|integer',
            'type_name' => 'required|in:web,pos,both',
        ]);
        $data['is_active'] = true;
        PickupTime::create($data);
        return redirect()->back()->with('message', 'Pickup time created successfully');
    }

    public function update(Request $request, $id)
    {
        $row = PickupTime::findOrFail($id);
        $data = $request->validate([
            'from_time' => 'required|string|max:20',
            'to_time' => 'required|string|max:20',
            'sort_order' => 'nullable|integer',
            'type_name' => 'required|in:web,pos,both',
            'is_active' => 'required|boolean',
        ]);
        $row->update($data);
        return redirect()->back()->with('message', 'Pickup time updated successfully');
    }

    public function destroy($id)
    {
        $row = PickupTime::findOrFail($id);
        $row->delete();
        return redirect()->back()->with('message', 'Pickup time deleted successfully');
    }

    public function inlineStatus(Request $request, $id)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $row = PickupTime::findOrFail($id);
        $row->is_active = $request->boolean('is_active');
        $row->save();
        return response()->json(['success' => true]);
    }

    public function inlineSort(Request $request, $id)
    {
        $request->validate(['sort_order' => 'nullable|integer']);
        $row = PickupTime::findOrFail($id);
        $row->sort_order = $request->input('sort_order');
        $row->save();
        return response()->json(['success' => true]);
    }
}
