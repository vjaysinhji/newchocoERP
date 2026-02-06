<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\RawMaterial;
use App\Models\Unit;
use App\Models\Adjustment;
use App\Models\ProductAdjustment;
use Illuminate\Support\Facades\DB;
use Auth;
use Spatie\Permission\Models\Role;

class RawMaterialAdjustmentController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if( $role->hasPermissionTo('rawmaterials-index') ) {
            // Get adjustments that are for raw materials only
            $lims_adjustment_all = Adjustment::orderBy('id', 'desc')
                ->where('type', 'raw_material')
                ->get();
            
            return view('backend.rawmaterial.adjustment.index', compact('lims_adjustment_all'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function getRawMaterial($id)
    {
        // Get raw materials for the selected warehouse
        // Since raw materials don't use warehouse pivot, we'll get all active raw materials
        // For now, let's get ALL active raw materials without filtering by category/brand/unit type
        // This will help us debug if the issue is with the filtering logic
        
        $lims_raw_material_data = RawMaterial::where('is_active', true)
            ->select('id', 'name', 'code', 'qty', 'cost')
            ->get();
        
        $raw_material_code = [];
        $raw_material_name = [];
        $raw_material_qty = [];
        $raw_material_cost = [];
        
        foreach ($lims_raw_material_data as $raw_material) {
            if($raw_material->code && $raw_material->name) {
                $raw_material_qty[] = $raw_material->qty ?? 0;
                $raw_material_code[] = $raw_material->code;
                $raw_material_name[] = $raw_material->name;
                $raw_material_cost[] = $raw_material->cost ?? 0;
            }
        }
        
        $raw_material_data = [];
        $raw_material_data[] = $raw_material_code;
        $raw_material_data[] = $raw_material_name;
        $raw_material_data[] = $raw_material_qty;
        $raw_material_data[] = $raw_material_cost;
        
        // Log for debugging
        \Log::info('Raw Material Adjustment - getRawMaterial', [
            'warehouse_id' => $id,
            'total_raw_materials' => count($raw_material_code),
            'codes' => $raw_material_code
        ]);
        
        return $raw_material_data;
    }

    public function limsRawMaterialSearch(Request $request)
    {
        $raw_material_code = explode("(", $request['data']);
        $raw_material_info = explode("|", $request['data']);
        $raw_material_code[0] = rtrim($raw_material_code[0], " ");

        // Simplified search - get raw material by code without complex filtering
        $lims_raw_material_data = RawMaterial::where([
            ['code', $raw_material_code[0]],
            ['is_active', true]
        ])->first();

        if(!$lims_raw_material_data) {
            \Log::info('Raw Material not found', ['code' => $raw_material_code[0], 'data' => $request['data']]);
            return [];
        }

        $raw_material = [];
        $raw_material[] = $lims_raw_material_data->name;
        $raw_material[] = $lims_raw_material_data->code;
        $raw_material[] = $lims_raw_material_data->id;
        $raw_material[] = null;
        $raw_material[] = isset($raw_material_info[1]) ? $raw_material_info[1] : ($lims_raw_material_data->cost ?? 0);

        $unit_id = $lims_raw_material_data->unit_id;
        $unit_name = 'Unit';
        $units_list = [];
        if ($unit_id) {
            $unit = Unit::find($unit_id);
            if ($unit) {
                $unit_name = $unit->unit_name;
                $units_list = Unit::where('id', $unit_id)->orWhere('base_unit', $unit_id)->get(['id', 'unit_name', 'operator', 'operation_value'])->toArray();
            }
        }
        $raw_material[] = $unit_id;
        $raw_material[] = $unit_name;
        $raw_material[] = $units_list;

        return $raw_material;
    }

    public function create()
    {
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $lims_raw_material_list = RawMaterial::where('is_active', true)
            ->where(function($query) {
                $query->whereHas('category', function($q) {
                    $q->where(function($q2) {
                        $q2->whereNull('type')->orWhere('type', 'raw_material');
                    });
                })
                ->orWhereDoesntHave('category');
            })
            ->where(function($query) {
                $query->whereHas('brand', function($q) {
                    $q->where(function($q2) {
                        $q2->whereNull('type')->orWhere('type', 'raw_material');
                    });
                })
                ->orWhereNull('brand_id');
            })
            ->where(function($query) {
                $query->whereHas('unit', function($q) {
                    $q->where(function($q2) {
                        $q2->whereNull('type')->orWhere('type', 'raw_material');
                    });
                })
                ->orWhereNull('unit_id');
            })
            ->select('id', 'name', 'code')
            ->get();
        
        return view('backend.rawmaterial.adjustment.create', compact('lims_warehouse_list', 'lims_raw_material_list'));
    }

    public function store(Request $request)
    {
        try{
            DB::beginTransaction();
            $data = $request->except('document');
            
            if( isset($data['stock_count_id']) ){
                $lims_stock_count_data = \App\Models\StockCount::find($data['stock_count_id']);
                $lims_stock_count_data->is_adjusted = true;
                $lims_stock_count_data->save();
            }
            
            $data['reference_no'] = 'rm-adr-' . date("Ymd") . '-'. date("his");
            $data['type'] = 'raw_material'; // Add type to distinguish from product adjustments
            
            $document = $request->document;
            if ($document) {
                $documentName = $document->getClientOriginalName();
                if (!file_exists(public_path('documents/rawmaterial_adjustment/'))) {
                    mkdir(public_path('documents/rawmaterial_adjustment/'), 0777, true);
                }
                $document->move(public_path('documents/rawmaterial_adjustment'), $documentName);
                $data['document'] = $documentName;
            }
            
            $lims_adjustment_data = Adjustment::create($data);

            $raw_material_id = $data['product_id']; // Using product_id field name for compatibility
            $raw_material_code = $data['product_code'];
            $qty = $data['qty'];
            if(isset($data['unit_cost']))
                $unit_cost = $data['unit_cost'];
            $action = $data['action'];

            foreach ($raw_material_id as $key => $rm_id) {
                $lims_raw_material_data = RawMaterial::find($rm_id);
                
                // Adjust raw material quantity directly (no warehouse pivot table)
                if($action[$key] == '-') {
                    $lims_raw_material_data->qty -= $qty[$key];
                }
                elseif($action[$key] == '+') {
                    $lims_raw_material_data->qty += $qty[$key];
                }
                $lims_raw_material_data->save();

                // Create product adjustment record (using same table for compatibility)
                $raw_material_adjustment['product_id'] = $rm_id;
                $raw_material_adjustment['variant_id'] = null; // No variants for raw materials
                $raw_material_adjustment['adjustment_id'] = $lims_adjustment_data->id;
                $raw_material_adjustment['qty'] = $qty[$key];
                if(isset($data['unit_cost']))
                    $raw_material_adjustment['unit_cost'] = $unit_cost[$key];
                $raw_material_adjustment['action'] = $action[$key];
                ProductAdjustment::create($raw_material_adjustment);
            }
            DB::commit();
            return redirect('rawmaterial-adjustment')->with('message', __('db.Data inserted successfully'));
        }catch(\Throwable $e){
            DB::rollBack();
            return redirect('rawmaterial-adjustment')->with('not_permitted', __('db.Something Error Please try again'));
        }
    }

    public function edit($id)
    {
        $lims_adjustment_data = Adjustment::where('id', $id)
            ->where('type', 'raw_material')
            ->firstOrFail();
            
        $lims_raw_material_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        return view('backend.rawmaterial.adjustment.edit', compact('lims_adjustment_data', 'lims_warehouse_list', 'lims_raw_material_adjustment_data'));
    }

    public function update(Request $request, $id)
    {
        try{
            DB::beginTransaction();
            $data = $request->except('document');
            $lims_adjustment_data = Adjustment::where('id', $id)
                ->where('type', 'raw_material')
                ->firstOrFail();

            $document = $request->document;
            if ($document) {
                if($lims_adjustment_data->document && file_exists(public_path('documents/rawmaterial_adjustment/') . $lims_adjustment_data->document)) {
                    unlink(public_path('documents/rawmaterial_adjustment/') . $lims_adjustment_data->document);
                }
                $documentName = $document->getClientOriginalName();
                if (!file_exists(public_path('documents/rawmaterial_adjustment/'))) {
                    mkdir(public_path('documents/rawmaterial_adjustment/'), 0777, true);
                }
                $document->move(public_path('documents/rawmaterial_adjustment'), $documentName);
                $data['document'] = $documentName;
            }

            $lims_raw_material_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();

            $raw_material_id = $data['product_id'];
            $raw_material_code = $data['product_code'];
            $qty = $data['qty'];
            $unit_cost = $data['unit_cost'];
            $action = $data['action'];
            $old_raw_material_id = [];
            
            foreach ($lims_raw_material_adjustment_data as $key => $raw_material_adjustment_data) {
                $old_raw_material_id[] = $raw_material_adjustment_data->product_id;
                $lims_raw_material_data = RawMaterial::find($raw_material_adjustment_data->product_id);
                
                // Reverse the previous adjustment
                if($raw_material_adjustment_data->action == '-') {
                    $lims_raw_material_data->qty += $raw_material_adjustment_data->qty;
                }
                elseif($raw_material_adjustment_data->action == '+') {
                    $lims_raw_material_data->qty -= $raw_material_adjustment_data->qty;
                }
                $lims_raw_material_data->save();
                
                if( !(in_array($old_raw_material_id[$key], $raw_material_id)) ) {
                    $raw_material_adjustment_data->delete();
                }
            }

            foreach ($raw_material_id as $key => $rm_id) {
                $lims_raw_material_data = RawMaterial::find($rm_id);
                
                // Apply new adjustment
                if($action[$key] == '-') {
                    $lims_raw_material_data->qty -= $qty[$key];
                }
                elseif($action[$key] == '+') {
                    $lims_raw_material_data->qty += $qty[$key];
                }
                $lims_raw_material_data->save();

                $raw_material_adjustment['product_id'] = $rm_id;
                $raw_material_adjustment['variant_id'] = null;
                $raw_material_adjustment['adjustment_id'] = $id;
                $raw_material_adjustment['unit_cost'] = $unit_cost[$key];
                $raw_material_adjustment['action'] = $action[$key];

                if( in_array($rm_id, $old_raw_material_id) ) {
                    $adjustment = ProductAdjustment::where([
                        ['adjustment_id', $id],
                        ['product_id', $rm_id]
                    ])->first();
                    if($action[$key] == '-'){
                        $raw_material_adjustment['qty'] = $adjustment->qty - $qty[$key];
                    }
                    elseif($action[$key] == '+'){
                        $raw_material_adjustment['qty'] = $adjustment->qty + $qty[$key];
                    }
                    $adjustment->update($raw_material_adjustment);
                }
                else{
                    $raw_material_adjustment['qty'] = $qty[$key];
                    ProductAdjustment::create($raw_material_adjustment);
                }
            }
            $lims_adjustment_data->update($data);
            DB::commit();
            return redirect('rawmaterial-adjustment')->with('message', __('db.Data updated successfully'));
        }catch(\Throwable $e){
            DB::rollBack();
            return redirect('rawmaterial-adjustment')->with('not_permitted', __('db.Something Error Please try again'));
        }
    }

    public function deleteBySelection(Request $request)
    {
        $adjustment_id = $request['adjustmentIdArray'];
        foreach ($adjustment_id as $id) {
            $lims_adjustment_data = Adjustment::where('id', $id)
                ->where('type', 'raw_material')
                ->first();
                
            if($lims_adjustment_data) {
                if($lims_adjustment_data->document && file_exists(public_path('documents/rawmaterial_adjustment/') . $lims_adjustment_data->document)) {
                    unlink(public_path('documents/rawmaterial_adjustment/') . $lims_adjustment_data->document);
                }

                $lims_raw_material_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
                foreach ($lims_raw_material_adjustment_data as $raw_material_adjustment_data) {
                    $lims_raw_material_data = RawMaterial::find($raw_material_adjustment_data->product_id);
                    
                    // Reverse the adjustment
                    if($raw_material_adjustment_data->action == '-'){
                        $lims_raw_material_data->qty += $raw_material_adjustment_data->qty;
                    }
                    elseif($raw_material_adjustment_data->action == '+'){
                        $lims_raw_material_data->qty -= $raw_material_adjustment_data->qty;
                    }
                    $lims_raw_material_data->save();
                    $raw_material_adjustment_data->delete();
                }
                $lims_adjustment_data->delete();
            }
        }
        return 'Data deleted successfully';
    }

    public function destroy($id)
    {
        $lims_adjustment_data = Adjustment::where('id', $id)
            ->where('type', 'raw_material')
            ->firstOrFail();
            
        $lims_raw_material_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
        foreach ($lims_raw_material_adjustment_data as $raw_material_adjustment_data) {
            $lims_raw_material_data = RawMaterial::find($raw_material_adjustment_data->product_id);
            
            // Reverse the adjustment
            if($raw_material_adjustment_data->action == '-'){
                $lims_raw_material_data->qty += $raw_material_adjustment_data->qty;
            }
            elseif($raw_material_adjustment_data->action == '+'){
                $lims_raw_material_data->qty -= $raw_material_adjustment_data->qty;
            }
            $lims_raw_material_data->save();
            $raw_material_adjustment_data->delete();
        }
        $lims_adjustment_data->delete();
        
        if($lims_adjustment_data->document && file_exists(public_path('documents/rawmaterial_adjustment/') . $lims_adjustment_data->document)) {
            unlink(public_path('documents/rawmaterial_adjustment/') . $lims_adjustment_data->document);
        }

        return redirect('rawmaterial-adjustment')->with('not_permitted', __('db.Data deleted successfully'));
    }
}
