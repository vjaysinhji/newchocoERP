<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Governorate;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        $areas = Area::with('governorate')
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get();

        $governorates = Governorate::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get();

        return view('backend.delivery.area.index', compact('areas', 'governorates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'charge' => 'nullable|numeric',
            'sort_order' => 'nullable|integer',
            'governorate_id' => 'required|exists:governorates,id',
        ]);

        $data['is_active'] = true;

        Area::create($data);

        return redirect()->back()->with('message', 'Area/City created successfully');
    }

    public function update(Request $request, $id)
    {
        $area = Area::findOrFail($id);

        $data = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'charge' => 'nullable|numeric',
            'sort_order' => 'nullable|integer',
            'governorate_id' => 'required|exists:governorates,id',
            'is_active' => 'required|boolean',
        ]);

        $area->update($data);

        return redirect()->back()->with('message', 'Area/City updated successfully');
    }

    public function destroy($id)
    {
        $area = Area::findOrFail($id);
        $area->delete();

        return redirect()->back()->with('message', 'Area/City deleted successfully');
    }

    public function inlineStatus(Request $request, $id)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $row = Area::findOrFail($id);
        $row->is_active = $request->boolean('is_active');
        $row->save();
        return response()->json(['success' => true]);
    }

    public function inlineSort(Request $request, $id)
    {
        $request->validate(['sort_order' => 'nullable|integer']);
        $row = Area::findOrFail($id);
        $row->sort_order = $request->input('sort_order');
        $row->save();
        return response()->json(['success' => true]);
    }
}
