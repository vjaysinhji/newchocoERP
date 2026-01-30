<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Basement;
use App\Models\Adjustment;
use App\Models\ProductAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class WarehouseStoreAdjustmentController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if( $role->hasPermissionTo('warehouse-stores-index') ) {
            $lims_adjustment_all = Adjustment::orderBy('id', 'desc')
                ->where('type', 'warehouse_store')
                ->get();
            
            return view('backend.warehousestore.adjustment.index', compact('lims_adjustment_all'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function getBasement($id)
    {
        $lims_basement_data = Basement::where('is_active', true)
            ->where(function($query) {
                $query->whereHas('category', function($q) {
                    $q->where(function($q2) {
                        $q2->whereNull('type')->orWhere('type', 'warehouse_store');
                    });
                })
                ->orWhereDoesntHave('category');
            })
            ->where(function($query) {
                $query->whereHas('brand', function($q) {
                    $q->where(function($q2) {
                        $q2->whereNull('type')->orWhere('type', 'warehouse_store');
                    });
                })
                ->orWhereNull('brand_id');
            })
            ->where(function($query) {
                $query->whereHas('unit', function($q) {
                    $q->where(function($q2) {
                        $q2->whereNull('type')->orWhere('type', 'warehouse_store');
                    });
                })
                ->orWhereNull('unit_id');
            })
            ->select('id', 'name', 'code', 'qty', 'cost')
            ->get();
        
        $basement_code = [];
        $basement_name = [];
        $basement_qty = [];
        $basement_cost = [];
        
        foreach ($lims_basement_data as $basement) {
            if($basement->code && $basement->name) {
                $basement_qty[] = $basement->qty ?? 0;
                $basement_code[] = $basement->code;
                $basement_name[] = $basement->name;
                $basement_cost[] = $basement->cost ?? 0;
            }
        }
        
        $basement_data = [];
        $basement_data[] = $basement_code;
        $basement_data[] = $basement_name;
        $basement_data[] = $basement_qty;
        $basement_data[] = $basement_cost;
        
        return $basement_data;
    }

    public function limsBasementSearch(Request $request)
    {
        $basement_code = explode("(", $request['data']);
        $basement_info = explode("|", $request['data']);
        $basement_code[0] = rtrim($basement_code[0], " ");

        $lims_basement_data = Basement::where([
            ['code', $basement_code[0]],
            ['is_active', true]
        ])->first();

        if(!$lims_basement_data) {
            return [];
        }

        $basement = [];
        $basement[] = $lims_basement_data->name;
        $basement[] = $lims_basement_data->code;
        $basement[] = $lims_basement_data->id;
        $basement[] = null;
        $basement[] = isset($basement_info[1]) ? $basement_info[1] : ($lims_basement_data->cost ?? 0);
        $quantity = explode("|", $request['data']);
        if (count($quantity) >= 3) {
            $basement[] = $quantity[2];
        }
        
        return $basement;
    }

    public function create()
    {
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $lims_basement_list = Basement::where('is_active', true)
            ->where(function($query) {
                $query->whereHas('category', function($q) {
                    $q->where(function($q2) {
                        $q2->whereNull('type')->orWhere('type', 'warehouse_store');
                    });
                })
                ->orWhereDoesntHave('category');
            })
            ->where(function($query) {
                $query->whereHas('brand', function($q) {
                    $q->where(function($q2) {
                        $q2->whereNull('type')->orWhere('type', 'warehouse_store');
                    });
                })
                ->orWhereNull('brand_id');
            })
            ->where(function($query) {
                $query->whereHas('unit', function($q) {
                    $q->where(function($q2) {
                        $q2->whereNull('type')->orWhere('type', 'warehouse_store');
                    });
                })
                ->orWhereNull('unit_id');
            })
            ->select('id', 'name', 'code')
            ->get();
        
        return view('backend.warehousestore.adjustment.create', compact('lims_warehouse_list', 'lims_basement_list'));
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
            
            $data['reference_no'] = 'ws-adr-' . date("Ymd") . '-'. date("his");
            $data['type'] = 'warehouse_store';
            
            $document = $request->document;
            if ($document) {
                $documentName = $document->getClientOriginalName();
                if (!file_exists(public_path('documents/warehousestore_adjustment/'))) {
                    mkdir(public_path('documents/warehousestore_adjustment/'), 0777, true);
                }
                $document->move(public_path('documents/warehousestore_adjustment'), $documentName);
                $data['document'] = $documentName;
            }
            
            $lims_adjustment_data = Adjustment::create($data);

            $basement_id = $data['product_id'];
            $qty = $data['qty'];
            if(isset($data['unit_cost']))
                $unit_cost = $data['unit_cost'];
            $action = $data['action'];

            foreach ($basement_id as $key => $bm_id) {
                $lims_basement_data = Basement::find($bm_id);
                
                if($action[$key] == '-') {
                    $lims_basement_data->qty -= $qty[$key];
                }
                elseif($action[$key] == '+') {
                    $lims_basement_data->qty += $qty[$key];
                }
                $lims_basement_data->save();

                $basement_adjustment['product_id'] = $bm_id;
                $basement_adjustment['variant_id'] = null;
                $basement_adjustment['adjustment_id'] = $lims_adjustment_data->id;
                $basement_adjustment['qty'] = $qty[$key];
                if(isset($data['unit_cost']))
                    $basement_adjustment['unit_cost'] = $unit_cost[$key];
                $basement_adjustment['action'] = $action[$key];
                ProductAdjustment::create($basement_adjustment);
            }
            DB::commit();
            return redirect('warehouse-store-adjustment')->with('message', __('db.Data inserted successfully'));
        }catch(\Throwable $e){
            DB::rollBack();
            return redirect('warehouse-store-adjustment')->with('not_permitted', __('db.Something Error Please try again'));
        }
    }

    public function edit($id)
    {
        $lims_adjustment_data = Adjustment::where('id', $id)
            ->where('type', 'warehouse_store')
            ->firstOrFail();
            
        $lims_basement_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        return view('backend.warehousestore.adjustment.edit', compact('lims_adjustment_data', 'lims_warehouse_list', 'lims_basement_adjustment_data'));
    }

    public function update(Request $request, $id)
    {
        try{
            DB::beginTransaction();
            $data = $request->except('document');
            $lims_adjustment_data = Adjustment::where('id', $id)
                ->where('type', 'warehouse_store')
                ->firstOrFail();

            $document = $request->document;
            if ($document) {
                if($lims_adjustment_data->document && file_exists(public_path('documents/warehousestore_adjustment/') . $lims_adjustment_data->document)) {
                    unlink(public_path('documents/warehousestore_adjustment/') . $lims_adjustment_data->document);
                }
                $documentName = $document->getClientOriginalName();
                if (!file_exists(public_path('documents/warehousestore_adjustment/'))) {
                    mkdir(public_path('documents/warehousestore_adjustment/'), 0777, true);
                }
                $document->move(public_path('documents/warehousestore_adjustment'), $documentName);
                $data['document'] = $documentName;
            }

            $lims_basement_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();

            $basement_id = $data['product_id'];
            $qty = $data['qty'];
            $unit_cost = $data['unit_cost'];
            $action = $data['action'];
            $old_basement_id = [];
            
            foreach ($lims_basement_adjustment_data as $key => $basement_adjustment_data) {
                $old_basement_id[] = $basement_adjustment_data->product_id;
                $lims_basement_data = Basement::find($basement_adjustment_data->product_id);
                
                if($basement_adjustment_data->action == '-') {
                    $lims_basement_data->qty += $basement_adjustment_data->qty;
                }
                elseif($basement_adjustment_data->action == '+') {
                    $lims_basement_data->qty -= $basement_adjustment_data->qty;
                }
                $lims_basement_data->save();
                
                if( !(in_array($old_basement_id[$key], $basement_id)) ) {
                    $basement_adjustment_data->delete();
                }
            }

            foreach ($basement_id as $key => $bm_id) {
                $lims_basement_data = Basement::find($bm_id);
                
                if($action[$key] == '-') {
                    $lims_basement_data->qty -= $qty[$key];
                }
                elseif($action[$key] == '+') {
                    $lims_basement_data->qty += $qty[$key];
                }
                $lims_basement_data->save();

                $basement_adjustment['product_id'] = $bm_id;
                $basement_adjustment['variant_id'] = null;
                $basement_adjustment['adjustment_id'] = $id;
                $basement_adjustment['unit_cost'] = $unit_cost[$key];
                $basement_adjustment['action'] = $action[$key];

                if( in_array($bm_id, $old_basement_id) ) {
                    $adjustment = ProductAdjustment::where([
                        ['adjustment_id', $id],
                        ['product_id', $bm_id]
                    ])->first();
                    if($action[$key] == '-'){
                        $basement_adjustment['qty'] = $adjustment->qty - $qty[$key];
                    }
                    elseif($action[$key] == '+'){
                        $basement_adjustment['qty'] = $adjustment->qty + $qty[$key];
                    }
                    $adjustment->update($basement_adjustment);
                }
                else{
                    $basement_adjustment['qty'] = $qty[$key];
                    ProductAdjustment::create($basement_adjustment);
                }
            }
            $lims_adjustment_data->update($data);
            DB::commit();
            return redirect('warehouse-store-adjustment')->with('message', __('db.Data updated successfully'));
        }catch(\Throwable $e){
            DB::rollBack();
            return redirect('warehouse-store-adjustment')->with('not_permitted', __('db.Something Error Please try again'));
        }
    }

    public function deleteBySelection(Request $request)
    {
        $adjustment_id = $request['adjustmentIdArray'];
        foreach ($adjustment_id as $id) {
            $lims_adjustment_data = Adjustment::where('id', $id)
                ->where('type', 'warehouse_store')
                ->first();
                
            if($lims_adjustment_data) {
                if($lims_adjustment_data->document && file_exists(public_path('documents/warehousestore_adjustment/') . $lims_adjustment_data->document)) {
                    unlink(public_path('documents/warehousestore_adjustment/') . $lims_adjustment_data->document);
                }

                $lims_basement_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
                foreach ($lims_basement_adjustment_data as $basement_adjustment_data) {
                    $lims_basement_data = Basement::find($basement_adjustment_data->product_id);
                    
                    if($basement_adjustment_data->action == '-'){
                        $lims_basement_data->qty += $basement_adjustment_data->qty;
                    }
                    elseif($basement_adjustment_data->action == '+'){
                        $lims_basement_data->qty -= $basement_adjustment_data->qty;
                    }
                    $lims_basement_data->save();
                    $basement_adjustment_data->delete();
                }
                $lims_adjustment_data->delete();
            }
        }
        return 'Data deleted successfully';
    }

    public function destroy($id)
    {
        $lims_adjustment_data = Adjustment::where('id', $id)
            ->where('type', 'warehouse_store')
            ->firstOrFail();
            
        $lims_basement_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
        foreach ($lims_basement_adjustment_data as $basement_adjustment_data) {
            $lims_basement_data = Basement::find($basement_adjustment_data->product_id);
            
            if($basement_adjustment_data->action == '-'){
                $lims_basement_data->qty += $basement_adjustment_data->qty;
            }
            elseif($basement_adjustment_data->action == '+'){
                $lims_basement_data->qty -= $basement_adjustment_data->qty;
            }
            $lims_basement_data->save();
            $basement_adjustment_data->delete();
        }
        $lims_adjustment_data->delete();
        
        if($lims_adjustment_data->document && file_exists(public_path('documents/warehousestore_adjustment/') . $lims_adjustment_data->document)) {
            unlink(public_path('documents/warehousestore_adjustment/') . $lims_adjustment_data->document);
        }

        return redirect('warehouse-store-adjustment')->with('not_permitted', __('db.Data deleted successfully'));
    }
}
