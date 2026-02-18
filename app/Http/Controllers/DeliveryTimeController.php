<?php

namespace App\Http\Controllers;

use App\Models\DeliveryTime;
use Illuminate\Http\Request;

class DeliveryTimeController extends Controller
{
    public function index()
    {
        $times = DeliveryTime::orderBy('sort_order')->orderBy('from_time')->get();
        return view('backend.delivery.timing.index', compact('times'));
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
        DeliveryTime::create($data);
        return redirect()->back()->with('message', 'Delivery time created successfully');
    }

    public function update(Request $request, $id)
    {
        $time = DeliveryTime::findOrFail($id);
        $data = $request->validate([
            'from_time' => 'required|string|max:20',
            'to_time' => 'required|string|max:20',
            'sort_order' => 'nullable|integer',
            'type_name' => 'required|in:web,pos,both',
            'is_active' => 'required|boolean',
        ]);
        $time->update($data);
        return redirect()->back()->with('message', 'Delivery time updated successfully');
    }

    public function destroy($id)
    {
        $time = DeliveryTime::findOrFail($id);
        $time->delete();
        return redirect()->back()->with('message', 'Delivery time deleted successfully');
    }

    public function inlineStatus(Request $request, $id)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $row = DeliveryTime::findOrFail($id);
        $row->is_active = $request->boolean('is_active');
        $row->save();
        return response()->json(['success' => true]);
    }

    public function inlineSort(Request $request, $id)
    {
        $request->validate(['sort_order' => 'nullable|integer']);
        $row = DeliveryTime::findOrFail($id);
        $row->sort_order = $request->input('sort_order');
        $row->save();
        return response()->json(['success' => true]);
    }
}
