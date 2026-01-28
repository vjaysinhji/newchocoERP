<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Currency;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\RawMaterial;
use App\Models\PosSetting;
use App\Traits\TenantInfo;
use App\Helpers\DateHelper;
use App\Models\CustomField;
use App\Traits\StaffAccess;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\PaymentService;

class RawPurchaseController extends Controller
{
    use TenantInfo, StaffAccess;

    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('raw-purchases-index')) {
            if($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;

            if($request->input('purchase_status'))
                $purchase_status = $request->input('purchase_status');
            else
                $purchase_status = 0;

            if($request->input('payment_status'))
                $payment_status = $request->input('payment_status');
            else
                $payment_status = 0;

            if($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date = $request->input('ending_date');
            }
            else {
                $starting_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d') )))));
                $ending_date = date("Y-m-d");
            }
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';
            $lims_pos_setting_data = PosSetting::select('stripe_public_key')->latest()->first();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_account_list = Account::where('is_active', true)->get();
            $custom_fields = CustomField::where([
                                ['belongs_to', 'purchase'],
                                ['is_table', true]
                            ])->pluck('name');
            $field_name = [];
            foreach($custom_fields as $fieldName) {
                $field_name[] = str_replace(" ", "_", strtolower($fieldName));
            }
            $currency_list = Currency::where('is_active', true)->get();
            return view('backend.raw-purchase.index', compact( 'lims_account_list', 'lims_warehouse_list', 'all_permission', 'lims_pos_setting_data', 'warehouse_id', 'starting_date', 'ending_date', 'purchase_status', 'payment_status', 'custom_fields', 'field_name', 'currency_list'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function create()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('raw-purchases-add')){
            $lims_supplier_list = Supplier::where('is_active', true)->get();
            if(Auth::user()->role_id > 2) {
                $lims_warehouse_list = Warehouse::where([
                    ['is_active', true],
                    ['id', Auth::user()->warehouse_id]
                ])->get();
            }
            else {
                $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            }
            $lims_tax_list = Tax::where('is_active', true)->get();
            // Only load active raw materials - regular products are not allowed
            $lims_raw_material_list = RawMaterial::where('is_active', true)->get();
            $currency_list = Currency::where('is_active', true)->get();
            $custom_fields = CustomField::where('belongs_to', 'purchase')->get();
            $lims_account_list = Account::select('id', 'name', 'account_no','total_balance', 'is_default')->where('is_active', true)->get();
            return view('backend.raw-purchase.create', compact('lims_supplier_list', 'lims_warehouse_list', 'lims_tax_list', 'lims_raw_material_list', 'currency_list', 'custom_fields', 'lims_account_list'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function store(Request $request)
    {
        if(isset($request->reference_no)) {
            $this->validate($request, [
                'reference_no' => [
                    'max:191', 'required', 'unique:purchases'
                ],
            ]);
        }

        DB::beginTransaction();

        try {
            $data = $request->except('document');
            $data['user_id'] = Auth::id();
            $data['purchase_type'] = 'raw_material'; // Mark as raw material purchase

            if(!isset($data['reference_no']))
            {
                $data['reference_no'] = 'rpr-' . date("Ymd") . '-'. date("his");
            }

            $document = $request->document;

            if ($document) {
                $v = Validator::make(
                    [
                        'extension' => strtolower($request->document->getClientOriginalExtension()),
                    ],
                    [
                        'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                    ]
                );
                if ($v->fails())
                    return redirect()->back()->withErrors($v->errors());

                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if(!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                    $document->move(public_path('documents/purchase'), $documentName);
                }
                else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                    $document->move(public_path('documents/purchase'), $documentName);
                }
                $data['document'] = $documentName;
            }

            if (isset($data['created_at'])) {
                $data['created_at'] = normalize_to_sql_datetime($data['created_at']);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
            }

            $data['paid_amount'] = 0; // important as paid amount will be updated by PaymentService

            $lims_purchase_data = Purchase::create($data);
            
            //inserting data for custom fields
            $custom_field_data = [];
            $custom_fields = CustomField::where('belongs_to', 'purchase')->select('name', 'type')->get();
            foreach ($custom_fields as $type => $custom_field) {
                $field_name = str_replace(' ', '_', strtolower($custom_field->name));
                if(isset($data[$field_name])) {
                    if($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                        $custom_field_data[$field_name] = implode(",", $data[$field_name]);
                    else
                        $custom_field_data[$field_name] = $data[$field_name];
                }
            }
            if(count($custom_field_data))
                DB::table('purchases')->where('id', $lims_purchase_data->id)->update($custom_field_data);
            
            $raw_material_id = $data['raw_material_id'];
            $raw_material_code = $data['raw_material_code'];
            $qty = $data['qty'];
            $recieved = $data['recieved'];
            $purchase_unit = $data['purchase_unit'];
            $unit_cost = $data['unit_cost'];
            $net_unit_cost = $data['net_unit_cost'];
            $net_unit_margin = $data['net_unit_margin'];
            $net_unit_margin_type = $data['net_unit_margin_type'];
            $net_unit_price = $data['net_unit_price'];
            $discount = $data['discount'];
            $tax_rate = $data['tax_rate'];
            $tax = $data['tax'];
            $total = $data['subtotal'];
            $log_data['item_description'] = '';

            // Validate that all items are raw materials and active
            $valid_raw_material_ids = RawMaterial::where('is_active', true)
                ->whereIn('id', $raw_material_id)
                ->pluck('id')
                ->toArray();
            
            foreach ($raw_material_id as $id) {
                if (!in_array($id, $valid_raw_material_ids)) {
                    DB::rollback();
                    return redirect()->back()->with('not_permitted', 'Invalid raw material selected. Only active raw materials can be purchased.');
                }
            }

            foreach ($raw_material_id as $i => $id) {
                $lims_purchase_unit_data  = Unit::where('unit_name', $purchase_unit[$i])->first();

                if ($lims_purchase_unit_data->operator == '*') {
                    $quantity = $recieved[$i] * $lims_purchase_unit_data->operation_value;
                } else {
                    $quantity = $recieved[$i] / $lims_purchase_unit_data->operation_value;
                }
                
                $lims_raw_material_data = RawMaterial::where('id', $id)
                    ->where('is_active', true)
                    ->first();
                
                if (!$lims_raw_material_data) {
                    DB::rollback();
                    return redirect()->back()->with('not_permitted', 'Raw material not found or inactive. Only active raw materials can be purchased.');
                }
                
                // Update Raw Material quantity directly (no warehouse qty for raw materials)
                $lims_raw_material_data->qty = ($lims_raw_material_data->qty ?? 0) + $quantity;
                $lims_raw_material_data->cost = $unit_cost[$i];
                $lims_raw_material_data->price = $net_unit_price[$i];
                $lims_raw_material_data->save();

                $log_data['item_description'] .= $lims_raw_material_data->name. '-'. $qty[$i].' '.$lims_purchase_unit_data->unit_code.'<br>';

                $raw_material_purchase = [];
                $raw_material_purchase['purchase_id'] = $lims_purchase_data->id;
                $raw_material_purchase['raw_material_id'] = $id;
                $raw_material_purchase['qty'] = $qty[$i];
                $raw_material_purchase['recieved'] = $recieved[$i];
                $raw_material_purchase['purchase_unit_id'] = $lims_purchase_unit_data->id;
                $raw_material_purchase['net_unit_cost'] = $net_unit_cost[$i];
                $raw_material_purchase['net_unit_margin'] = $net_unit_margin[$i];
                $raw_material_purchase['net_unit_margin_type'] = $net_unit_margin_type[$i];
                $raw_material_purchase['net_unit_price'] = $net_unit_price[$i];
                $raw_material_purchase['discount'] = $discount[$i];
                $raw_material_purchase['tax_rate'] = $tax_rate[$i];
                $raw_material_purchase['tax'] = $tax[$i];
                $raw_material_purchase['total'] = $total[$i];
                
                DB::table('raw_material_purchases')->insert($raw_material_purchase);
            }

            if ($data['payment_status'] == 3 || $data['payment_status'] == 4) {
                if (isset($data['payment_at'])) {
                    $data['payment_at'] = normalize_to_sql_datetime($data['payment_at']);
                } else {
                    $data['payment_at'] = date('Y-m-d H:i:s');
                }
                $pay_data = [
                    'paying_amount' => array_sum($data['paying_amount']),
                    'amount' => $data['payment_status'] == 1 ? 0 : array_sum($data['amount']),
                    'paid_by_id' => $data['paid_by_id'][0],
                    'cheque_no' => $data['cheque_no'] ?? null,
                    'account_id' => $data['account_id'],
                    'payment_note' => $data['payment_note'] ?? null,
                    'purchase_id' => $lims_purchase_data->id,
                    'currency_id' => $lims_purchase_data->currency_id,
                    'exchange_rate' => $lims_purchase_data->exchange_rate ?? 1,
                    'payment_at' => $data['payment_at']
                ];

                $response = (new PaymentService())->payForPurchase($pay_data);

                if (!$response['status']) {
                    DB::rollback();
                    throw new \Exception($response['message']);
                }
            }

            //creating log
            $log_data['action'] = 'Raw Purchase Created';
            $log_data['user_id'] = Auth::id();
            $log_data['reference_no'] = $lims_purchase_data->reference_no;
            $log_data['date'] = $lims_purchase_data->created_at->toDateString();
            $log_data['admin_message'] = Auth::user()->name . ' has created a raw material purchase. Reference No: ' .$lims_purchase_data->reference_no;
            $log_data['user_email'] = Auth::user()->email;
            $log_data['user_name'] = Auth::user()->name;
            $log_data['user_message'] = 'You just created a raw material purchase. Reference No: ' .$lims_purchase_data->reference_no;
            $this->createActivityLog($log_data);

            DB::commit();

            return redirect('raw-purchases')->with('message', __('db.Raw Purchase created successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect('raw-purchases/create')->with('not_permitted', 'Transaction failed: ' . $e->getMessage());
        }
    }

    public function limsRawMaterialSearch(Request $request)
    {
        // Only search for active raw materials - ensure no regular products can be added
        $raw_material_code = explode("|", $request['data']);
        $raw_material_code[0] = rtrim($raw_material_code[0], " ");
        $lims_raw_material_data = RawMaterial::where([
                                ['code', $raw_material_code[0]],
                                ['is_active', true]
                            ])->first();
        
        if(!$lims_raw_material_data) {
            $lims_raw_material_data = RawMaterial::where([
                                ['name', $raw_material_code[1]],
                                ['is_active', true]
                            ])->first();
        }
        
        if(!$lims_raw_material_data) {
            return response()->json(['error' => 'Raw Material not found. Only active raw materials can be purchased.'], 404);
        }
        
        $raw_material[] = $lims_raw_material_data->name;
        $raw_material[] = $lims_raw_material_data->code;
        $raw_material[] = $lims_raw_material_data->cost;
        $raw_material['profit_margin'] = 0; // Raw materials don't have profit margin
        $raw_material['profit_margin_type'] = 'percentage';
        $raw_material['product_price'] = $lims_raw_material_data->price;

        if ($lims_raw_material_data->tax_id) {
            $lims_tax_data = Tax::find($lims_raw_material_data->tax_id);
            $raw_material[] = $lims_tax_data->rate;
            $raw_material[] = $lims_tax_data->name;
        } else {
            $raw_material[] = 0;
            $raw_material[] = 'No Tax';
        }
        $raw_material[] = $lims_raw_material_data->tax_method ?? 1;

        $units = Unit::where("base_unit", $lims_raw_material_data->unit_id)
                    ->orWhere('id', $lims_raw_material_data->unit_id)
                    ->get();
        $unit_name = array();
        $unit_operator = array();
        $unit_operation_value = array();
        foreach ($units as $unit) {
            if ($lims_raw_material_data->purchase_unit_id == $unit->id) {
                array_unshift($unit_name, $unit->unit_name);
                array_unshift($unit_operator, $unit->operator);
                array_unshift($unit_operation_value, $unit->operation_value);
            } else {
                $unit_name[]  = $unit->unit_name;
                $unit_operator[] = $unit->operator;
                $unit_operation_value[] = $unit->operation_value;
            }
        }

        $raw_material[] = implode(",", $unit_name) . ',';
        $raw_material[] = implode(",", $unit_operator) . ',';
        $raw_material[] = implode(",", $unit_operation_value) . ',';
        $raw_material[] = $lims_raw_material_data->id;
        $raw_material[] = false; // is_batch
        $raw_material[] = false; // is_imei
        
        return $raw_material;
    }

    public function purchaseData(Request $request)
    {
        $general_setting = GeneralSetting::select('show_products_details_in_purchase_table')->first();
        
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
            6 => 'grand_total',
            8 => 'paid_amount',
        );
        if ($general_setting->show_products_details_in_purchase_table) {
            $columns = array(
                1 => 'created_at',
                2 => 'reference_no',
                8 => 'grand_total',
                10 => 'paid_amount',
            );
        } 

        $warehouse_id = $request->input('warehouse_id');
        $purchase_status = $request->input('purchase_status');
        $payment_status = $request->input('payment_status');

        $q = Purchase::where('purchase_type', 'raw_material')
                ->whereDate('created_at', '>=' ,$request->input('starting_date'))
                ->whereDate('created_at', '<=' ,$request->input('ending_date'));
        //check staff access
        $this->staffAccessCheck($q);
        if($warehouse_id)
            $q = $q->where('warehouse_id', $warehouse_id);
        if($purchase_status)
            $q = $q->where('status', $purchase_status);
        if($payment_status)
            $q = $q->where('payment_status', $payment_status);

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'purchases.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        //fetching custom fields data
        $custom_fields = CustomField::where([
                        ['belongs_to', 'purchase'],
                        ['is_table', true]
                    ])->pluck('name');
        $field_names = [];
        foreach($custom_fields as $fieldName) {
            $field_names[] = str_replace(" ", "_", strtolower($fieldName));
        }
        if(empty($request->input('search.value'))) {
            $q = Purchase::with('supplier', 'warehouse')
                ->where('purchase_type', 'raw_material')
                ->whereDate('created_at', '>=' ,$request->input('starting_date'))
                ->whereDate('created_at', '<=' ,$request->input('ending_date'))
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);
            //check staff access
            $this->staffAccessCheck($q);
            if($warehouse_id)
                $q = $q->where('warehouse_id', $warehouse_id);
            if($purchase_status)
                $q = $q->where('status', $purchase_status);
            if($payment_status)
                $q = $q->where('payment_status', $payment_status);
            $purchases = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $searchDate = date('Y-m-d', strtotime(str_replace('/', '-', $search)));

            $q = Purchase::query()
                ->join('raw_material_purchases', 'purchases.id', '=', 'raw_material_purchases.purchase_id')
                ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
                ->leftJoin('raw_materials', 'raw_material_purchases.raw_material_id', '=', 'raw_materials.id')
                ->whereNull('purchases.deleted_at')
                ->where('purchases.purchase_type', 'raw_material')
                ->whereBetween(DB::raw('DATE(purchases.created_at)'), [
                    $request->input('starting_date'),
                    $request->input('ending_date')
                ]);

            // ✅ APPLY FILTERS FIRST (DO NOT MOVE THESE)
            if ($warehouse_id) {
                $q->where('purchases.warehouse_id', $warehouse_id);
            }

            if ($purchase_status) {
                $q->where('purchases.status', $purchase_status);
            }

            if ($payment_status) {
                $q->where('purchases.payment_status', $payment_status);
            }

            // ✅ ACCESS CONTROL
            if (Auth::user()->role_id > 2) {
                if (config('staff_access') == 'own') {
                    $q->where('purchases.user_id', Auth::id());
                } elseif (config('staff_access') == 'warehouse') {
                    $q->where('purchases.warehouse_id', Auth::user()->warehouse_id);
                }
            }

            // ✅ SAFE SEARCH GROUP
            $q->where(function ($query) use ($search, $searchDate, $field_names) {

                if (strtotime($searchDate)) {
                    $query->orWhereDate('purchases.created_at', $searchDate);
                }

                $query->orWhere('purchases.reference_no', 'LIKE', "%{$search}%")
                    ->orWhere('suppliers.name', 'LIKE', "%{$search}%")
                    ->orWhere('raw_materials.name', 'LIKE', "%{$search}%")
                    ->orWhere('raw_materials.code', 'LIKE', "%{$search}%");

                foreach ($field_names as $field_name) {
                    $query->orWhere('purchases.' . $field_name, 'LIKE', "%{$search}%");
                }

            });

            // ✅ COUNT
            $totalFiltered = $q->distinct('purchases.id')->count('purchases.id');

            // ✅ SORTING
            $q->orderBy($order, $dir);

            // ✅ FETCH
            $purchases = $q->select('purchases.*')
                        ->groupBy('purchases.id')
                        ->skip($start)
                        ->take($limit)
                        ->get();
        }

        $data = array();
        if(!empty($purchases))
        {
            foreach ($purchases as $key=>$purchase)
            {
                $user = $purchase->user;

                $nestedData['id'] = $purchase->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($purchase->created_at->toDateString()));
                $nestedData['reference_no'] = $purchase->reference_no;
                $nestedData['created_by'] = $user->name ?? 'N/A';

                if($purchase->supplier_id) {
                    $supplier = $purchase->supplier;
                }
                else {
                    $supplier = new Supplier();
                }

                // raw material details and qty
                $rawMaterialNames = [];
                $rawMaterialQtys = [];
                $raw_material_purchases = DB::table('raw_material_purchases')
                    ->join('raw_materials', 'raw_material_purchases.raw_material_id', '=', 'raw_materials.id')
                    ->where('raw_material_purchases.purchase_id', $purchase->id)
                    ->select('raw_materials.name', 'raw_material_purchases.qty')
                    ->get();
                
                $total_raw_materials = $raw_material_purchases->count();
                foreach ($raw_material_purchases as $index => $rm) {
                    if( $index + 1 < $total_raw_materials){
                        $rawMaterialNames[] = '<div style="border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-bottom: 4px;">' . e($rm->name) . '</div>';
                    }else{
                        $rawMaterialNames[] = '<div style=" padding-bottom: 4px; margin-bottom: 4px;">' . e($rm->name) . '</div>';
                    }
                    $rawMaterialQtys[] = '<div style="padding-bottom: 4px; margin-bottom: 4px;"><span class="badge badge-primary">' . e($rm->qty) . '</span></div>';
                }

                $nestedData['supplier'] = $purchase->supplier->name ?? '';
                if ($general_setting->show_products_details_in_purchase_table) {
                    $nestedData['products'] = implode('', $rawMaterialNames);
                    $nestedData['products_qty'] = implode('', $rawMaterialQtys);
                }

                if ($purchase->status == 1) {
                    $nestedData['purchase_status'] = '<div class="badge badge-success">' . __('db.Recieved') . '</div>';
                    $purchase_status = __('db.Recieved');
                }
                elseif($purchase->status == 2){
                    $nestedData['purchase_status'] = '<div class="badge badge-success">'.__('db.Partial').'</div>';
                    $purchase_status = __('db.Partial');
                }
                elseif($purchase->status == 3){
                    $nestedData['purchase_status'] = '<div class="badge badge-danger">'.__('db.Pending').'</div>';
                    $purchase_status = __('db.Pending');
                }
                else{
                    $nestedData['purchase_status'] = '<div class="badge badge-danger">'.__('db.Ordered').'</div>';
                    $purchase_status = __('db.Ordered');
                }

                if($purchase->payment_status == 1)
                    $nestedData['payment_status'] = '<div class="badge badge-danger">'.__('db.Due').'</div>';
                else
                    $nestedData['payment_status'] = '<div class="badge badge-success">'.__('db.Paid').'</div>';

                if(!$purchase->exchange_rate || $purchase->exchange_rate == 0)
                    $purchase->exchange_rate = 1;

                $nestedData['grand_total'] = number_format($purchase->grand_total / $purchase->exchange_rate, config('decimal'));
                $returned_amount = DB::table('return_purchases')->where('purchase_id', $purchase->id)->sum('grand_total');
                $nestedData['returned_amount'] = number_format($returned_amount / $purchase->exchange_rate, config('decimal'));
                $nestedData['paid_amount'] = number_format($purchase->paid_amount / $purchase->exchange_rate, config('decimal'));
                $nestedData['due'] = number_format(
                    max(0, ($purchase->grand_total - $returned_amount - $purchase->paid_amount) / $purchase->exchange_rate),
                    config('decimal')
                );
                //fetching custom fields data
                foreach($field_names as $field_name) {
                    $nestedData[$field_name] = $purchase->$field_name ?? '';
                }
                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.__("db.action").'
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" class="btn btn-link view"><i class="fa fa-eye"></i> '.__('db.View').'</button>
                                </li>';
                if(in_array("raw-purchases-edit", $request['all_permission']))
                    $nestedData['options'] .= '<li>
                        <a href="'.route('raw-purchases.edit', $purchase->id).'" class="btn btn-link"><i class="dripicons-document-edit"></i> '.__('db.edit').'</a>
                        </li>';
                if(in_array("purchase-payment-index", $request['all_permission']))
                    $nestedData['options'] .=
                        '<li>
                            <button type="button" class="get-payment btn btn-link" data-id = "'.$purchase->id.'"><i class="fa fa-money"></i> '.__('db.View Payment').'</button>
                        </li>';

                if(in_array("purchase-payment-add", $request['all_permission'])) {
                    $currency_code_name = $purchase->currency->code ?? 'USD';
                    $nestedData['options'] .=
                        '<li>
                            <button
                                type="button"
                                class="add-payment btn btn-link"
                                data-id="'.$purchase->id.'"
                                data-currency_id="'.$purchase->currency_id.'"
                                data-currency_name="'.$currency_code_name.'"
                                data-exchange_rate="'.$purchase->exchange_rate.'"
                                data-toggle="modal"
                                data-target="#add-payment">
                                <i class="fa fa-plus"></i> '.__('db.Add Payment').'
                            </button>
                        </li>';
                }
                if(in_array("raw-purchases-delete", $request['all_permission']))
                    $nestedData['options'] .= \Form::open(["route" => ["raw-purchases.destroy", $purchase->id], "method" => "DELETE"] ).'
                            <li>
                              <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> '.__("db.delete").'</button>
                            </li>'.\Form::close().'
                        </ul>
                    </div>';

                // data for purchase details by one click                
                if($purchase->currency_id) {
                    $currency = Currency::select('code')->find($purchase->currency_id);
                    if($currency)
                        $currency_code = $currency->code;
                }
                else
                    $currency_code = 'N/A';

                $nestedData['purchase'] = array( '[ "'.date(config('date_format'), strtotime($purchase->created_at->toDateString())).'"', ' "'.$purchase->reference_no.'"', ' "'.$purchase_status.'"',  ' "'.$purchase->id.'"', ' "'.$purchase->warehouse->name.'"', ' "'.$purchase->warehouse->phone.'"', ' "'.preg_replace('/\s+/S', " ", $purchase->warehouse->address).'"', ' "'.$supplier->name.'"', ' "'.$supplier->company_name.'"', ' "'.$supplier->email.'"', ' "'.$supplier->phone_number.'"', ' "'.preg_replace('/\s+/S', " ", $supplier->address).'"', ' "'.$supplier->city.'"', ' "'.$purchase->total_tax.'"', ' "'.$purchase->total_discount.'"', ' "'.$purchase->total_cost.'"', ' "'.$purchase->order_tax.'"', ' "'.$purchase->order_tax_rate.'"', ' "'.$purchase->order_discount.'"', ' "'.$purchase->shipping_cost.'"', ' "'.$purchase->grand_total.'"', ' "'.$purchase->paid_amount.'"', ' "'.preg_replace('/\s+/S', " ", $purchase->note).'"', ' "'.$user->name.'"', ' "'.$user->email.'"', ' "'.$purchase->document.'"', ' "'.$currency_code.'"', ' "'.$purchase->exchange_rate.'"]'
                );
                $data[] = $nestedData;
            }
        }
        
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );
        echo json_encode($json_data);
    }

    public function rawMaterialPurchaseData($id)
    {
        try {
            $lims_raw_material_purchase_data = DB::table('raw_material_purchases')
                ->where('purchase_id', $id)
                ->get();
            $raw_material_purchase = [];
            foreach ($lims_raw_material_purchase_data as $key => $rm_purchase_data) {
                $raw_material = RawMaterial::find($rm_purchase_data->raw_material_id);
                $unit = Unit::find($rm_purchase_data->purchase_unit_id);
                
                $raw_material_purchase[0][$key] = $raw_material->name . ' (' . $raw_material->code . ')';
                $raw_material_purchase[1][$key] = $rm_purchase_data->qty;
                $raw_material_purchase[2][$key] = $unit->unit_code;
                $raw_material_purchase[3][$key] = $rm_purchase_data->tax;
                $raw_material_purchase[4][$key] = $rm_purchase_data->tax_rate;
                $raw_material_purchase[5][$key] = $rm_purchase_data->discount;
                $raw_material_purchase[6][$key] = $rm_purchase_data->total;
                $raw_material_purchase[7][$key] = ''; // batch_no - not applicable for raw materials
                $raw_material_purchase[8][$key] = 0; // returned - not applicable for raw materials
            }
            return $raw_material_purchase;
        } catch (\Exception $e) {
            return 'Something is wrong!';
        }
    }

    public function addPayment(Request $request)
    {
        $data = $request->except('_token');

        if (isset($data['payment_at'])) {
            $data['payment_at'] = normalize_to_sql_datetime($data['payment_at']);
        } else {
            $data['payment_at'] = date('Y-m-d H:i:s');
        }

        $response = (new PaymentService())->payForPurchase($data);

        if ($response['status']) {
            return redirect('raw-purchases')->with('message', __('db.Payment created successfully'));
        }
        return redirect('raw-purchases')->with('not_permitted', 'Payment failed!');
    }

    public function getPayment($id)
    {
        $lims_payment_list = Payment::where('purchase_id', $id)->get();
        $date = [];
        $payment_reference = [];
        $paid_amount = [];
        $paying_method = [];
        $payment_id = [];
        $payment_note = [];
        $cheque_no = [];
        $change = [];
        $paying_amount = [];
        $account_name = [];
        $account_id = [];
        $payment_at = [];
        foreach ($lims_payment_list as $payment) {
            // added currency for previously inserted data
            if (!$payment->currency_id) {
                $lims_purchase_data = Purchase::find($payment->purchase_id);

                if ($lims_purchase_data) {
                    $payment->currency_id = $lims_purchase_data->currency_id;
                    $payment->exchange_rate = $lims_purchase_data->exchange_rate ?? 1;
                }
            }

            $date[] = date(config('date_format'), strtotime($payment->created_at->toDateString())) . ' '. $payment->created_at->toTimeString();
            $payment_reference[] = $payment->payment_reference;
            $paid_amount[] = $payment->amount;
            $change[] = $payment->change;
            $paying_method[] = $payment->paying_method;
            $paying_amount[] = $payment->amount + $payment->change;
            if($payment->paying_method == 'Cheque'){
                $lims_payment_cheque_data = \App\Models\PaymentWithCheque::where('payment_id',$payment->id)->first();
                $cheque_no[] = $lims_payment_cheque_data ? $lims_payment_cheque_data->cheque_no : null;
            }
            else{
                $cheque_no[] = null;
            }
            $payment_id[] = $payment->id;
            $payment_note[] = $payment->payment_note;
            $lims_account_data = Account::find($payment->account_id);
            if($lims_account_data) {
                $account_name[] = $lims_account_data->name;
                $account_id[] = $lims_account_data->id;
            }
            else {
                $account_name[] = 'N/A';
                $account_id[] = 0;
            }

            $payment->payment_at = $payment->payment_at ?? $payment->created_at;
            $payment->save();
            $payment_at[] = date(config('date_format'), strtotime($payment->payment_at->toDateString()));
        }
        $payments[] = $date;
        $payments[] = $payment_reference;
        $payments[] = $paid_amount;
        $payments[] = $paying_method;
        $payments[] = $payment_id;
        $payments[] = $payment_note;
        $payments[] = $cheque_no;
        $payments[] = $change;
        $payments[] = $paying_amount;
        $payments[] = $account_name;
        $payments[] = $account_id;
        $payments[] = $payment_at;

        return $payments;
    }

    public function updatePayment(Request $request)
    {
        $data = $request->all();
        $lims_payment_data = Payment::find($data['payment_id']);
        $lims_purchase_data = Purchase::find($lims_payment_data->purchase_id);
        //updating purchase table
        $amount_dif = $lims_payment_data->amount - $data['edit_amount'];
        $lims_purchase_data->paid_amount = $lims_purchase_data->paid_amount - $amount_dif;
        $balance = $lims_purchase_data->grand_total - $lims_purchase_data->paid_amount;
        if($balance > 0 || $balance < 0)
            $lims_purchase_data->payment_status = 1;
        elseif ($balance == 0)
            $lims_purchase_data->payment_status = 2;
        $lims_purchase_data->save();

        if (isset($data['payment_at'])) {
            $data['payment_at'] = normalize_to_sql_datetime($data['payment_at']);
        } else {
            $data['payment_at'] = date('Y-m-d H:i:s');
        }

        //updating payment data
        $lims_payment_data->account_id = $data['account_id'];
        $lims_payment_data->amount = $data['edit_amount'];
        $lims_payment_data->change = $data['edit_paying_amount'] - $data['edit_amount'];
        $lims_payment_data->payment_note = $data['edit_payment_note'];
        $lims_payment_data->payment_at = $data['payment_at'];
        $lims_payment_data->currency_id = $lims_purchase_data->currency_id;
        $lims_payment_data->exchange_rate = $lims_purchase_data->exchange_rate ?? 1;
        $lims_pos_setting_data = PosSetting::latest()->first();
        if($data['edit_paid_by_id'] == 1)
            $lims_payment_data->paying_method = 'Cash';
        elseif ($data['edit_paid_by_id'] == 2)
            $lims_payment_data->paying_method = 'Gift Card';
        elseif ($data['edit_paid_by_id'] == 3 && $lims_pos_setting_data && $lims_pos_setting_data->stripe_secret_key) {
            \Stripe\Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
            $token = $data['stripeToken'];
            $amount = $data['edit_amount'];
            if($lims_payment_data->paying_method == 'Credit Card'){
                $lims_payment_with_credit_card_data = \App\Models\PaymentWithCreditCard::where('payment_id', $lims_payment_data->id)->first();

                \Stripe\Refund::create(array(
                  "charge" => $lims_payment_with_credit_card_data->charge_id,
                ));

                $charge = \Stripe\Charge::create([
                    'amount' => $amount * 100,
                    'currency' => 'usd',
                    'source' => $token,
                ]);

                $lims_payment_with_credit_card_data->charge_id = $charge->id;
                $lims_payment_with_credit_card_data->save();
            }
            elseif($lims_pos_setting_data->stripe_secret_key) {
                // Charge the Customer
                $charge = \Stripe\Charge::create([
                    'amount' => $amount * 100,
                    'currency' => 'usd',
                    'source' => $token,
                ]);

                $data['charge_id'] = $charge->id;
                \App\Models\PaymentWithCreditCard::create($data);
            }
            $lims_payment_data->paying_method = 'Credit Card';
        }
        else{
            if($lims_payment_data->paying_method == 'Cheque'){
                $lims_payment_data->paying_method = 'Cheque';
                $lims_payment_cheque_data = \App\Models\PaymentWithCheque::where('payment_id', $data['payment_id'])->first();
                if($lims_payment_cheque_data) {
                    $lims_payment_cheque_data->cheque_no = $data['edit_cheque_no'];
                    $lims_payment_cheque_data->save();
                }
            }
            else{
                $lims_payment_data->paying_method = 'Cheque';
                $data['cheque_no'] = $data['edit_cheque_no'];
                \App\Models\PaymentWithCheque::create($data);
            }
        }
        $lims_payment_data->save();
        return redirect('raw-purchases')->with('message', __('db.Payment updated successfully'));
    }

    public function deletePayment(Request $request)
    {
        $lims_payment_data = Payment::find($request['id']);
        $lims_purchase_data = Purchase::where('id', $lims_payment_data->purchase_id)->first();
        $lims_purchase_data->paid_amount -= $lims_payment_data->amount;
        $balance = $lims_purchase_data->grand_total - $lims_purchase_data->paid_amount;
        if($balance > 0 || $balance < 0)
            $lims_purchase_data->payment_status = 1;
        elseif ($balance == 0)
            $lims_purchase_data->payment_status = 2;
        $lims_purchase_data->save();
        $lims_pos_setting_data = PosSetting::latest()->first();

        if($lims_payment_data->paying_method == 'Credit Card' && $lims_pos_setting_data && $lims_pos_setting_data->stripe_secret_key) {
            $lims_payment_with_credit_card_data = \App\Models\PaymentWithCreditCard::where('payment_id', $request['id'])->first();
            if($lims_payment_with_credit_card_data) {
                \Stripe\Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                \Stripe\Refund::create(array(
                  "charge" => $lims_payment_with_credit_card_data->charge_id,
                ));

                $lims_payment_with_credit_card_data->delete();
            }
        }
        elseif ($lims_payment_data->paying_method == 'Cheque') {
            $lims_payment_cheque_data = \App\Models\PaymentWithCheque::where('payment_id', $request['id'])->first();
            if($lims_payment_cheque_data) {
                $lims_payment_cheque_data->delete();
            }
        }
        $lims_payment_data->delete();
        return redirect('raw-purchases')->with('not_permitted', __('db.Payment deleted successfully'));
    }

    public function deleteBySelection(Request $request)
    {
        $purchase_id = $request['purchaseIdArray'];
        try {
            DB::beginTransaction();
            $deleted = [];
            foreach ($purchase_id as $id) {
                $role = Role::find(Auth::user()->role_id);
                if($role->hasPermissionTo('raw-purchases-delete')){
                    $lims_purchase_data = Purchase::where('id', $id)->where('purchase_type', 'raw_material')->first();
                    if(!$lims_purchase_data) {
                        continue;
                    }
                    
                    $lims_raw_material_purchase_data = DB::table('raw_material_purchases')
                        ->where('purchase_id', $id)
                        ->get();

                    $this->fileDelete(public_path('documents/purchase/'), $lims_purchase_data->document);

                    $lims_payment_data = Payment::where('purchase_id', $id)->get();
                    $log_data['item_description'] = '';
                    
                    foreach ($lims_raw_material_purchase_data as $rm_purchase_data) {
                        $lims_purchase_unit_data = Unit::find($rm_purchase_data->purchase_unit_id);
                        if ($lims_purchase_unit_data->operator == '*')
                            $recieved_qty = $rm_purchase_data->recieved * $lims_purchase_unit_data->operation_value;
                        else
                            $recieved_qty = $rm_purchase_data->recieved / $lims_purchase_unit_data->operation_value;

                        $lims_raw_material_data = RawMaterial::find($rm_purchase_data->raw_material_id);
                        
                        // Deduct quantity from Raw Material (no warehouse qty)
                        $lims_raw_material_data->qty = max(0, ($lims_raw_material_data->qty ?? 0) - $recieved_qty);
                        $lims_raw_material_data->save();

                        $log_data['item_description'] .= $lims_raw_material_data->name. '-'. $recieved_qty.' '.$lims_purchase_unit_data->unit_code.'<br>';
                    }
                    
                    $lims_pos_setting_data = PosSetting::latest()->first();
                    foreach ($lims_payment_data as $payment_data) {
                        if($payment_data->paying_method == "Cheque"){
                            $payment_with_cheque_data = \App\Models\PaymentWithCheque::where('payment_id', $payment_data->id)->first();
                            if($payment_with_cheque_data) {
                                $payment_with_cheque_data->delete();
                            }
                        }
                        elseif($payment_data->paying_method == "Credit Card" && $lims_pos_setting_data && $lims_pos_setting_data->stripe_secret_key) {
                            $payment_with_credit_card_data = \App\Models\PaymentWithCreditCard::where('payment_id', $payment_data->id)->first();
                            if($payment_with_credit_card_data) {
                                \Stripe\Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                                \Stripe\Refund::create(array(
                                "charge" => $payment_with_credit_card_data->charge_id,
                                ));

                                $payment_with_credit_card_data->delete();
                            }
                        }
                        $payment_data->delete();
                    }

                    $lims_purchase_data->deleted_by = Auth::id();
                    $lims_purchase_data->save();

                    //creating log
                    $log_data['action'] = 'Raw Purchase Deleted';
                    $log_data['user_id'] = Auth::id();
                    $log_data['reference_no'] = $lims_purchase_data->reference_no;
                    $log_data['date'] = $lims_purchase_data->created_at->toDateString();
                    $log_data['admin_message'] = Auth::user()->name . ' has deleted a raw material purchase. Reference No: ' .$lims_purchase_data->reference_no;
                    $log_data['user_email'] = Auth::user()->email;
                    $log_data['user_name'] = Auth::user()->name;
                    $log_data['user_message'] = 'You just deleted a raw material purchase. Reference No: ' .$lims_purchase_data->reference_no;
                    $this->createActivityLog($log_data);

                    DB::table('raw_material_purchases')->where('purchase_id', $id)->delete();
                    $lims_purchase_data->delete();
                    $deleted[] = $id;
                }
            }
            DB::commit();
            return response()->json(['deleted' => $deleted, 'message' =>  'Raw Purchase deleted successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['deleted' => [], 'message' =>  $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('raw-purchases-delete')){
            $lims_purchase_data = Purchase::where('id', $id)->where('purchase_type', 'raw_material')->first();
            if(!$lims_purchase_data) {
                return redirect('raw-purchases')->with('not_permitted', __('db.Purchase not found!'));
            }

            $this->fileDelete(public_path('documents/purchase/'), $lims_purchase_data->document);

            $lims_raw_material_purchase_data = DB::table('raw_material_purchases')
                ->where('purchase_id', $id)
                ->get();

            $lims_payment_data = Payment::where('purchase_id', $id)->get();
            $log_data['item_description'] = '';
            
            foreach ($lims_raw_material_purchase_data as $rm_purchase_data) {
                $lims_purchase_unit_data = Unit::find($rm_purchase_data->purchase_unit_id);
                if ($lims_purchase_unit_data->operator == '*')
                    $recieved_qty = $rm_purchase_data->recieved * $lims_purchase_unit_data->operation_value;
                else
                    $recieved_qty = $rm_purchase_data->recieved / $lims_purchase_unit_data->operation_value;

                $lims_raw_material_data = RawMaterial::find($rm_purchase_data->raw_material_id);
                
                // Deduct quantity from Raw Material (no warehouse qty)
                $lims_raw_material_data->qty = max(0, ($lims_raw_material_data->qty ?? 0) - $recieved_qty);
                $lims_raw_material_data->save();

                $log_data['item_description'] .= $lims_raw_material_data->name. '-'. $recieved_qty.' '.$lims_purchase_unit_data->unit_code.'<br>';
            }
            
            $lims_pos_setting_data = PosSetting::latest()->first();
            foreach ($lims_payment_data as $payment_data) {
                if($payment_data->paying_method == "Cheque"){
                    $payment_with_cheque_data = \App\Models\PaymentWithCheque::where('payment_id', $payment_data->id)->first();
                    if($payment_with_cheque_data) {
                        $payment_with_cheque_data->delete();
                    }
                }
                elseif($payment_data->paying_method == "Credit Card" && $lims_pos_setting_data && $lims_pos_setting_data->stripe_secret_key) {
                    $payment_with_credit_card_data = \App\Models\PaymentWithCreditCard::where('payment_id', $payment_data->id)->first();
                    if($payment_with_credit_card_data) {
                        \Stripe\Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                        \Stripe\Refund::create(array(
                          "charge" => $payment_with_credit_card_data->charge_id,
                        ));

                        $payment_with_credit_card_data->delete();
                    }
                }
                $payment_data->delete();
            }

            $lims_purchase_data->deleted_by = Auth::id();
            $lims_purchase_data->save();

            //creating log
            $log_data['action'] = 'Raw Purchase Deleted';
            $log_data['user_id'] = Auth::id();
            $log_data['reference_no'] = $lims_purchase_data->reference_no;
            $log_data['date'] = $lims_purchase_data->created_at->toDateString();
            $log_data['admin_message'] = Auth::user()->name . ' has deleted a raw material purchase. Reference No: ' .$lims_purchase_data->reference_no;
            $log_data['user_email'] = Auth::user()->email;
            $log_data['user_name'] = Auth::user()->name;
            $log_data['user_message'] = 'You just deleted a raw material purchase. Reference No: ' .$lims_purchase_data->reference_no;
            $this->createActivityLog($log_data);

            DB::table('raw_material_purchases')->where('purchase_id', $id)->delete();
            $lims_purchase_data->delete();
            $this->fileDelete(public_path('documents/purchase/'), $lims_purchase_data->document);

            return redirect('raw-purchases')->with('not_permitted', __('db.Raw Purchase deleted successfully'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function edit($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('raw-purchases-edit')){
            $lims_supplier_list = Supplier::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_raw_material_list = RawMaterial::where('is_active', true)->get();
            $lims_purchase_data = Purchase::where('id', $id)->where('purchase_type', 'raw_material')->first();
            if(!$lims_purchase_data) {
                return redirect('raw-purchases')->with('not_permitted', __('db.Purchase not found!'));
            }
            
            $lims_raw_material_purchase_data = DB::table('raw_material_purchases')
                ->where('purchase_id', $id)
                ->get();
            
            $currency_list = Currency::where('is_active', true)->get();
            if($lims_purchase_data->exchange_rate)
                $currency_exchange_rate = $lims_purchase_data->exchange_rate;
            else
                $currency_exchange_rate = 1;
            $custom_fields = CustomField::where('belongs_to', 'purchase')->get();
            return view('backend.raw-purchase.edit', compact('lims_warehouse_list', 'lims_supplier_list', 'lims_raw_material_list', 'lims_tax_list', 'lims_purchase_data', 'lims_raw_material_purchase_data', 'currency_list', 'currency_exchange_rate', 'custom_fields'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function update(Request $request, $id)
    {
        $lims_purchase_data = Purchase::where('id', $id)->where('purchase_type', 'raw_material')->first();
        if(!$lims_purchase_data) {
            return redirect('raw-purchases')->with('not_permitted', __('db.Purchase not found!'));
        }
        
        $data = $request->except('document');
        $document = $request->document;
        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $this->fileDelete(public_path('documents/purchase/'), $lims_purchase_data->document);

            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = date("Ymdhis");
            if(!config('database.connections.saleprosaas_landlord')) {
                $documentName = $documentName . '.' . $ext;
                $document->move(public_path('documents/purchase'), $documentName);
            }
            else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move(public_path('documents/purchase'), $documentName);
            }
            $data['document'] = $documentName;
        }
        
        DB::beginTransaction();

        try {
            $balance = (float)$data['grand_total'] - (float)$data['paid_amount'];
            if ($balance < 0 || $balance > 0) {
                $data['payment_status'] = 1;
            } else {
                $data['payment_status'] = 2;
            }
            
            $lims_raw_material_purchase_data = DB::table('raw_material_purchases')
                ->where('purchase_id', $id)
                ->get();

            $data['created_at'] = date("Y-m-d", strtotime(str_replace("/", "-", $data['created_at']))) . ' '. date("H:i:s");
            $raw_material_id = $data['raw_material_id'];
            $raw_material_code = $data['raw_material_code'];
            $qty = $data['qty'];
            $recieved = $data['recieved'];
            $purchase_unit = $data['purchase_unit'];
            $unit_cost = $data['unit_cost'];
            $net_unit_cost = $data['net_unit_cost'];
            $net_unit_margin = $data['net_unit_margin'];
            $net_unit_margin_type = $data['net_unit_margin_type'];
            $net_unit_price = $data['net_unit_price'];
            $discount = $data['discount'];
            $tax_rate = $data['tax_rate'];
            $tax = $data['tax'];
            $total = $data['subtotal'];

            // Validate that all items are raw materials and active
            $valid_raw_material_ids = RawMaterial::where('is_active', true)
                ->whereIn('id', $raw_material_id)
                ->pluck('id')
                ->toArray();
            
            foreach ($raw_material_id as $rm_id) {
                if (!in_array($rm_id, $valid_raw_material_ids)) {
                    DB::rollback();
                    return redirect()->back()->with('not_permitted', 'Invalid raw material selected. Only active raw materials can be purchased.');
                }
            }

            // First, deduct old quantities
            foreach ($lims_raw_material_purchase_data as $i => $rm_purchase_data) {
                $old_recieved_value = $rm_purchase_data->recieved;
                $lims_purchase_unit_data = Unit::find($rm_purchase_data->purchase_unit_id);

                if ($lims_purchase_unit_data->operator == '*') {
                    $old_recieved_value = $old_recieved_value * $lims_purchase_unit_data->operation_value;
                } else {
                    $old_recieved_value = $old_recieved_value / $lims_purchase_unit_data->operation_value;
                }
                
                $lims_raw_material_data = RawMaterial::where('id', $rm_purchase_data->raw_material_id)
                    ->where('is_active', true)
                    ->first();
                
                if ($lims_raw_material_data) {
                    $lims_raw_material_data->qty = max(0, ($lims_raw_material_data->qty ?? 0) - $old_recieved_value);
                    $lims_raw_material_data->save();
                }
            }
            
            // Delete old raw material purchases
            DB::table('raw_material_purchases')->where('purchase_id', $id)->delete();

            $log_data['item_description'] = '';
            // Add new quantities
            foreach ($raw_material_id as $key => $rm_id) {
                $lims_purchase_unit_data = Unit::where('unit_name', $purchase_unit[$key])->first();
                if ($lims_purchase_unit_data->operator == '*') {
                    $new_recieved_value = $recieved[$key] * $lims_purchase_unit_data->operation_value;
                } else {
                    $new_recieved_value = $recieved[$key] / $lims_purchase_unit_data->operation_value;
                }

                $lims_raw_material_data = RawMaterial::where('id', $rm_id)
                    ->where('is_active', true)
                    ->first();
                
                if (!$lims_raw_material_data) {
                    DB::rollback();
                    return redirect()->back()->with('not_permitted', 'Raw material not found or inactive. Only active raw materials can be purchased.');
                }
                $lims_raw_material_data->qty = ($lims_raw_material_data->qty ?? 0) + $new_recieved_value;
                $lims_raw_material_data->cost = $unit_cost[$key];
                $lims_raw_material_data->price = $net_unit_price[$key];
                $lims_raw_material_data->save();
                
                $log_data['item_description'] .= $lims_raw_material_data->name. '-'. $qty[$key].' '.$lims_purchase_unit_data->unit_code.'<br>';

                $raw_material_purchase = [];
                $raw_material_purchase['purchase_id'] = $id;
                $raw_material_purchase['raw_material_id'] = $rm_id;
                $raw_material_purchase['qty'] = $qty[$key];
                $raw_material_purchase['recieved'] = $recieved[$key];
                $raw_material_purchase['purchase_unit_id'] = $lims_purchase_unit_data->id;
                $raw_material_purchase['net_unit_cost'] = $net_unit_cost[$key];
                $raw_material_purchase['net_unit_margin'] = $net_unit_margin[$key];
                $raw_material_purchase['net_unit_margin_type'] = $net_unit_margin_type[$key];
                $raw_material_purchase['net_unit_price'] = $net_unit_price[$key];
                $raw_material_purchase['discount'] = $discount[$key];
                $raw_material_purchase['tax_rate'] = $tax_rate[$key];
                $raw_material_purchase['tax'] = $tax[$key];
                $raw_material_purchase['total'] = $total[$key];
                
                DB::table('raw_material_purchases')->insert($raw_material_purchase);
            }

            $lims_purchase_data->update($data);

            //creating log
            $log_data['action'] = 'Raw Purchase Updated';
            $log_data['user_id'] = Auth::id();
            $log_data['reference_no'] = $lims_purchase_data->reference_no;
            $log_data['date'] = $lims_purchase_data->created_at->toDateString();
            $log_data['admin_message'] = Auth::user()->name . ' has updated a raw material purchase. Reference No: ' .$lims_purchase_data->reference_no;
            $log_data['user_email'] = Auth::user()->email;
            $log_data['user_name'] = Auth::user()->name;
            $log_data['user_message'] = 'You just updated a raw material purchase. Reference No: ' .$lims_purchase_data->reference_no;
            $this->createActivityLog($log_data);

            //inserting data for custom fields
            $custom_field_data = [];
            $custom_fields = CustomField::where('belongs_to', 'purchase')->select('name', 'type')->get();
            foreach ($custom_fields as $type => $custom_field) {
                $field_name = str_replace(' ', '_', strtolower($custom_field->name));
                if(isset($data[$field_name])) {
                    if($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                        $custom_field_data[$field_name] = implode(",", $data[$field_name]);
                    else
                        $custom_field_data[$field_name] = $data[$field_name];
                }
            }
            if(count($custom_field_data))
                DB::table('purchases')->where('id', $lims_purchase_data->id)->update($custom_field_data);

            DB::commit();
            return redirect('raw-purchases')->with('message', __('db.Raw Purchase updated successfully!'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect('raw-purchases')->with('not_permitted', 'Transaction failed: ' . $e->getMessage());
        }
    }
}
