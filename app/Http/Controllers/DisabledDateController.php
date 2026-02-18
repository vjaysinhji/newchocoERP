<?php

namespace App\Http\Controllers;

use App\Models\DisabledDate;
use Illuminate\Http\Request;

class DisabledDateController extends Controller
{
    public function index()
    {
        $disabledDates = DisabledDate::orderBy('date')
            ->orderBy('sort_order')
            ->get();

        return view('backend.delivery.disabled-date.index', compact('disabledDates'));
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

        DisabledDate::create($data);

        return redirect()->back()->with('message', 'Disabled date created successfully');
    }

    public function update(Request $request, $id)
    {
        $disabledDate = DisabledDate::findOrFail($id);

        $data = $request->validate([
            'date' => 'required|date',
            'reason_en' => 'nullable|string|max:255',
            'reason_ar' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'type_name' => 'required|in:web,pos,both',
            'is_active' => 'required|boolean',
        ]);

        $disabledDate->update($data);

        return redirect()->back()->with('message', 'Disabled date updated successfully');
    }

    public function destroy($id)
    {
        $disabledDate = DisabledDate::findOrFail($id);
        $disabledDate->delete();

        return redirect()->back()->with('message', 'Disabled date deleted successfully');
    }

    public function inlineStatus(Request $request, $id)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $row = DisabledDate::findOrFail($id);
        $row->is_active = $request->boolean('is_active');
        $row->save();
        return response()->json(['success' => true]);
    }

    public function inlineSort(Request $request, $id)
    {
        $request->validate(['sort_order' => 'nullable|integer']);
        $row = DisabledDate::findOrFail($id);
        $row->sort_order = $request->input('sort_order');
        $row->save();
        return response()->json(['success' => true]);
    }
}
