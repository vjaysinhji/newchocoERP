<?php

namespace App\Http\Controllers;

use App\Models\Governorate;
use Illuminate\Http\Request;

class GovernorateController extends Controller
{
    public function index()
    {
        $governorates = Governorate::orderBy('sort_order')->orderBy('name_en')->get();

        return view('backend.delivery.governorate.index', compact('governorates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'country' => 'required|string|max:191',
        ]);

        $data['is_active'] = true;

        Governorate::create($data);

        return redirect()->back()->with('message', 'Governorate/State created successfully');
    }

    public function update(Request $request, $id)
    {
        $governorate = Governorate::findOrFail($id);

        $data = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'country' => 'required|string|max:191',
            'is_active' => 'required|boolean',
        ]);

        $governorate->update($data);

        return redirect()->back()->with('message', 'Governorate/State updated successfully');
    }

    public function destroy($id)
    {
        $governorate = Governorate::findOrFail($id);
        $governorate->delete();

        return redirect()->back()->with('message', 'Governorate/State deleted successfully');
    }

    public function inlineStatus(Request $request, $id)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $row = Governorate::findOrFail($id);
        $row->is_active = $request->boolean('is_active');
        $row->save();
        return response()->json(['success' => true]);
    }

    public function inlineSort(Request $request, $id)
    {
        $request->validate(['sort_order' => 'nullable|integer']);
        $row = Governorate::findOrFail($id);
        $row->sort_order = $request->input('sort_order');
        $row->save();
        return response()->json(['success' => true]);
    }
}
