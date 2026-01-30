<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Basement;
use DB;
use App\Models\StockCount;
use Auth;
use Spatie\Permission\Models\Role;

class WarehouseStoreStockCountController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if( $role->hasPermissionTo('warehouse-stores-index') ) {
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_brand_list = Brand::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })
                ->get();
            $lims_category_list = Category::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })
                ->get();
            $general_setting = DB::table('general_settings')->latest()->first();
            if(Auth::user()->role_id > 2 && $general_setting->staff_access == 'own')
                $lims_stock_count_all = StockCount::orderBy('id', 'desc')
                    ->where('material_type', 'warehouse_store')
                    ->where('user_id', Auth::id())
                    ->get();
            else
                $lims_stock_count_all = StockCount::orderBy('id', 'desc')
                    ->where('material_type', 'warehouse_store')
                    ->get();
            
            $general_setting = DB::table('general_settings')->latest()->first();

            return view('backend.warehousestore.stock_count.index', compact('lims_warehouse_list', 'lims_brand_list', 'lims_category_list', 'lims_stock_count_all', 'general_setting'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['material_type'] = 'warehouse_store';
        
        if(isset($request->category_id) || isset($request->brand_id)){
            $data['type'] = "partial";
        }else{
            $data['type'] = "full";
        }
        
        if( isset($data['brand_id']) && isset($data['category_id']) ){
            $lims_basement_list = Basement::whereIn('category_id', $data['category_id'])
                ->whereIn('brand_id', $data['brand_id'])
                ->where('is_active', true)
                ->select('name', 'code', 'qty')
                ->get();

            $data['category_id'] = implode(",", $data['category_id']);
            $data['brand_id'] = implode(",", $data['brand_id']);
        }
        elseif( isset($data['category_id']) ){
            $lims_basement_list = Basement::whereIn('category_id', $data['category_id'])
                ->where('is_active', true)
                ->select('name', 'code', 'qty')
                ->get();

            $data['category_id'] = implode(",", $data['category_id']);
        }
        elseif( isset($data['brand_id']) ){
            $lims_basement_list = Basement::whereIn('brand_id', $data['brand_id'])
                ->where('is_active', true)
                ->select('name', 'code', 'qty')
                ->get();

            $data['brand_id'] = implode(",", $data['brand_id']);
        }
        else{
            $lims_basement_list = Basement::where('is_active', true)
                ->select('name', 'code', 'qty')
                ->get();
        }
        
        if( count($lims_basement_list) ){
            $csvData=array('Warehouse Store Name, Warehouse Store Code, IMEI or Serial Numbers, Counted');
            foreach ($lims_basement_list as $basement) {
                $csvData[]=$basement->name.','.$basement->code.',,'.($basement->qty ?? 0);
            }
            if (!file_exists(public_path().'/stock_count/warehouse_store/')) {
                mkdir(public_path().'/stock_count/warehouse_store/', 0777, true);
            }
            $filename= date('Ymd').'-'.date('his'). ".csv";
            $file_path= public_path().'/stock_count/warehouse_store/'.$filename;
            $file = fopen($file_path, "w+");
            foreach ($csvData as $cellData){
              fputcsv($file, explode(',', $cellData));
            }
            fclose($file);

            $data['user_id'] = Auth::id();
            $data['reference_no'] = 'ws-scr-' . date("Ymd") . '-'. date("his");
            $data['initial_file'] = $filename;
            $data['is_adjusted'] = false;
            StockCount::create($data);
            return redirect()->back()->with('message', __('db.Stock Count created successfully! Please download the initial file to complete it'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.No raw material found!'));
    }

    public function finalize(Request $request)
    {
        $ext = pathinfo($request->final_file->getClientOriginalName(), PATHINFO_EXTENSION);
        if($ext != 'csv')
            return redirect()->back()->with('not_permitted', __('db.Please upload a CSV file'));

        $data = $request->all();
        $document = $request->final_file;
        $documentName = date('Ymd').'-'.date('his'). ".csv";
        if (!file_exists(public_path('stock_count/warehouse_store/'))) {
            mkdir(public_path('stock_count/warehouse_store/'), 0777, true);
        }
        $document->move(public_path('stock_count/warehouse_store/'), $documentName);
        $data['final_file'] = $documentName;
        $lims_stock_count_data = StockCount::where('id', $data['stock_count_id'])
            ->where('material_type', 'warehouse_store')
            ->firstOrFail();
        $lims_stock_count_data->update($data);
        return redirect()->back()->with('message', __('db.Stock Count finalized successfully!'));
    }

    public function stockDif($id)
    {
        $lims_stock_count_data = StockCount::where('id', $id)
            ->where('material_type', 'warehouse_store')
            ->firstOrFail();
        $file_handle = fopen(public_path('stock_count/warehouse_store/').$lims_stock_count_data->final_file, 'r');
        $i = 0;
        $temp_dif = -1000000;
        $data = [];
        $basement = [];
        $expected = [];
        $counted = [];
        $difference = [];
        $cost = [];
        while( !feof($file_handle) ) {
            $current_line = fgetcsv($file_handle);
            if( $current_line && $i > 0){
                $basement_data = Basement::select('id','code','cost','qty')
                    ->where('code', $current_line[1])
                    ->first();
                if(!$basement_data) {
                    $basement_data = Basement::select('id','code','cost','qty')
                        ->where('code', 'LIKE', "%{$current_line[1]}%")
                        ->first();
                }
                if($basement_data) {
                    $basement[] = $current_line[0].' ['.$basement_data->code.']';
                    $expected[] = $basement_data->qty;
                    if(isset($current_line[3]) && $current_line[3]){
                        $difference[] = $temp_dif = $current_line[3] - $basement_data->qty;
                        $counted[] = $current_line[3];
                    }
                    else{
                        $difference[] = $temp_dif = $basement_data->qty * (-1);
                        $counted[] = 0;
                    }
                    $cost[] = ($basement_data->cost ?? 0) * $temp_dif;
                }
            }
            $i++;
        }
        fclose($file_handle);
        if($temp_dif == -1000000){
            $lims_stock_count_data->is_adjusted = true;
            $lims_stock_count_data->save();
        }
        if( count($basement) ) {
            $data[] = $basement;
            $data[] = $expected;
            $data[] = $counted;
            $data[] = $difference;
            $data[] = $cost;
            $data[] = $lims_stock_count_data->is_adjusted;
        }
        return $data;
    }

    public function qtyAdjustment($id)
    {
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $lims_stock_count_data = StockCount::where('id', $id)
            ->where('material_type', 'warehouse_store')
            ->firstOrFail();
        $warehouse_id = $lims_stock_count_data->warehouse_id;
        $file_handle = fopen(public_path('stock_count/warehouse_store/').$lims_stock_count_data->final_file, 'r');
        $i = 0;
        $basement_id = [];
        $names = [];
        $code = [];
        $qty = [];
        $action = [];
        while( !feof($file_handle) ) {
            $current_line = fgetcsv($file_handle);
            if( $current_line && $i > 0 ){
                $basement_data = Basement::select('id','code','qty')->where('code', $current_line[1])->first();
                if($basement_data) {
                    $basement_id[] = $basement_data->id;
                    $names[] = $current_line[0];
                    $code[] = $current_line[1];

                    if(isset($current_line[3]) && $current_line[3])
                        $temp_qty = $current_line[3] - $basement_data->qty;
                    else
                        $temp_qty = $basement_data->qty * (-1);

                    if($temp_qty < 0){
                        $qty[] = $temp_qty * (-1);
                        $action[] = '-';
                    }
                    else{
                        $qty[] = $temp_qty;
                        $action[] = '+';
                    }
                }
            }
            $i++;
        }
        fclose($file_handle);
        if(isset($basement_id) && count($basement_id) > 0) {
            return view('backend.warehousestore.stock_count.qty_adjustment', compact('lims_warehouse_list', 'warehouse_id', 'id', 'basement_id', 'names', 'code', 'qty', 'action'));
        } else {
            return redirect()->back()->with('not_permitted', __('db.No raw materials found for adjustment'));
        }
    }
}
