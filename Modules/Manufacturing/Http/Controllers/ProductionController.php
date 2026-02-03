<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Models\GeneralSetting;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Product_Warehouse;
use App\Models\Unit;
use App\Models\RawMaterial;
use Modules\Manufacturing\Entities\Production;
use Modules\Manufacturing\Entities\ProductProduction;
use App\Models\Tax;
use App\Models\Account;
use App\Models\PosSetting;
use Auth;
use App\Traits\StaffAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductionController extends Controller
{
    use StaffAccess;

    public function index(Request $request)
    {
        if(in_array('manufacturing',explode(',',config('addons')))) {
            if($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;

            if($request->input('status'))
                $status = $request->input('status');
            else
                $status = 0;

            if($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date = $request->input('ending_date');
            }
            else {
                $starting_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d') )))));
                $ending_date = date("Y-m-d");
            }

            $lims_pos_setting_data = PosSetting::select('stripe_public_key')->latest()->first();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_account_list = Account::where('is_active', true)->get();
            return view('manufacturing::production.index', compact('status', 'lims_account_list', 'lims_warehouse_list', 'lims_pos_setting_data', 'warehouse_id', 'starting_date', 'ending_date'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function productionData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
            4 => 'product_id',
            5 => 'warehouse_id',
            6 => 'total_qty',
            7 => 'grand_total',
        );

        $warehouse_id = $request->input('warehouse_id');
        $status = $request->input('status');

        $q = Production::whereDate('created_at', '>=' ,$request->input('starting_date'))->whereDate('created_at', '<=' ,$request->input('ending_date'));
        //check staff access
        $this->staffAccessCheck($q);
        if($warehouse_id)
            $q = $q->where('warehouse_id', $warehouse_id);
        if($status)
            $q = $q->where('status', $status);

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $orderCol = $request->input('order.0.column', 1);
        $orderCol = isset($columns[$orderCol]) ? $orderCol : 1;
        $order = 'productions.'.$columns[$orderCol];
        $dir = in_array(strtolower($request->input('order.0.dir', 'asc')), ['asc', 'desc']) ? $request->input('order.0.dir') : 'asc';

        if(empty($request->input('search.value'))) {
            $q = Production::with('user', 'warehouse')
                ->whereDate('created_at', '>=' ,$request->input('starting_date'))
                ->whereDate('created_at', '<=' ,$request->input('ending_date'))
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);
            //check staff access
            $this->staffAccessCheck($q);
            if($warehouse_id)
                $q = $q->where('warehouse_id', $warehouse_id);
            if($status)
                $q = $q->where('status', $status);

            $productions = $q->get();
        }
        else
            {
                $search = $request->input('search.value');
                $searchDate = date('Y-m-d', strtotime(str_replace('/', '-', $search)));

                $countQuery = Production::leftJoin('products', 'productions.product_id', '=', 'products.id')
                    ->whereDate('productions.created_at', '>=', $request->input('starting_date'))
                    ->whereDate('productions.created_at', '<=', $request->input('ending_date'))
                    ->where(function ($query) use ($search, $searchDate) {
                        if ($searchDate && strtotime($search)) {
                            $query->whereDate('productions.created_at', '=', $searchDate);
                        }
                        $query->orWhere('productions.reference_no', 'LIKE', "%{$search}%")
                            ->orWhere('products.name', 'LIKE', "%{$search}%");
                    });
                $this->staffAccessCheck($countQuery);
                if ($warehouse_id) $countQuery->where('productions.warehouse_id', $warehouse_id);
                if ($status) $countQuery->where('productions.status', $status);
                $totalFiltered = (int) $countQuery->select(DB::raw('COUNT(DISTINCT productions.id) as total'))->value('total');

                $q = Production::leftJoin('products', 'productions.product_id', '=', 'products.id')
                    ->with('warehouse', 'user')
                    ->whereDate('productions.created_at', '>=', $request->input('starting_date'))
                    ->whereDate('productions.created_at', '<=', $request->input('ending_date'))
                    ->where(function ($query) use ($search, $searchDate) {
                        if ($searchDate && strtotime($search)) {
                            $query->whereDate('productions.created_at', '=', $searchDate);
                        }
                        $query->orWhere('productions.reference_no', 'LIKE', "%{$search}%")
                            ->orWhere('products.name', 'LIKE', "%{$search}%");
                    });
                $this->staffAccessCheck($q);
                if ($warehouse_id) $q->where('productions.warehouse_id', $warehouse_id);
                if ($status) $q->where('productions.status', $status);
                $productions = $q->select('productions.*')->groupBy('productions.id')->orderBy($order, $dir)->offset($start)->limit($limit)->get();
            }

        $data = array();
        if(!empty($productions))
        {
            foreach ($productions as $key=>$production)
            {
                $rowStatus = $production->status == 1 ? __('db.Recieved') : __('db.Pending');
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($production->created_at->toDateString()));
                $nestedData['reference_no'] = $production->reference_no ?? '';
                if ($production->status == 1) {
                    $nestedData['status'] = '<div class="badge badge-success">'.__('db.Completed').'</div>';
                } else {
                    $nestedData['status'] = '<div class="badge badge-secondary">'.__('db.Pending').'</div>';
                }

                $nestedData['total_cost'] = number_format((float)($production->total_cost ?? 0), config('decimal'));
                $nestedData['product'] = $production->product ? $production->product->name : '';
                $nestedData['quantity'] = (int)($production->total_qty ?? 0);
                $nestedData['warehouse'] = $production->warehouse ? $production->warehouse->name : '';
                $nestedData['total_tax'] = number_format((float)($production->total_tax ?? 0), config('decimal'));
                $nestedData['shipping_cost'] = number_format((float)($production->shipping_cost ?? 0), config('decimal'));
                $nestedData['production_cost'] = number_format((float)($production->production_cost ?? 0), config('decimal'));
                $nestedData['grand_total'] = number_format((float)($production->grand_total ?? 0), config('decimal'));

                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.__("db.action").'
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" class="btn btn-link view"><i class="fa fa-eye"></i> '.__('db.View').'</button>
                                </li>
                                <li>
                                    <a href="'.route('productions.edit', $production->id).'" class="btn btn-link"><i class="dripicons-document-edit"></i> '.__('db.edit').'</a>
                                </li>';

                $nestedData['options'] .= \Form::open(["route" => ["productions.destroy", $production->id], "method" => "DELETE"] ).'
                        <li>
                            <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> '.__("db.delete").'</button>
                        </li>'.\Form::close().'
                    </ul>
                </div>';

                $user = $production->user;
                $nestedData['production'] = [
                    date(config('date_format'), strtotime($production->created_at->toDateString())),
                    $production->reference_no ?? '',
                    $rowStatus,
                    $production->id,
                    $production->warehouse ? $production->warehouse->name : '',
                    (float)($production->total_tax ?? 0),
                    (float)($production->total_cost ?? 0),
                    (float)($production->shipping_cost ?? 0),
                    (float)($production->grand_total ?? 0),
                    preg_replace('/\s+/S', ' ', $production->note ?? ''),
                    $user ? $user->name : '',
                    $user ? $user->email : '',
                    $production->document ?? '',
                    $production->batch_lot_number ?? '',
                    $production->expiry_date ? $production->expiry_date->format(config('date_format')) : '',
                ];
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


    public function create()
    {
        if(Auth::user()->role_id > 2) {
            $lims_warehouse_list = Warehouse::where([
                ['is_active', true],
                ['id', Auth::user()->warehouse_id]
            ])->get();
        }
        else {
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        }
        $lims_product_list = Product::where([
            ['is_recipe', 1],
            ['is_active', true]
        ])->get();
        $lims_tax_list = Tax::where('is_active', true)->get();
        $lims_product_list_without_variant = $this->productWithoutVariant();
        $lims_product_list_with_variant = $this->productWithVariant();
        $default_warehouse_id = Warehouse::where('is_active', true)
            ->whereRaw('LOWER(name) LIKE ?', ['%cold%storage%'])
            ->value('id');
        if (!$default_warehouse_id) {
            $default_warehouse_id = $lims_warehouse_list->first()?->id;
        }
        return view('manufacturing::production.create', compact('lims_product_list_with_variant','lims_product_list_without_variant', 'lims_warehouse_list', 'lims_product_list', 'lims_tax_list', 'default_warehouse_id'));
    }

    public function store(Request $request)
    {
        try{
        DB::beginTransaction();
        $data = $request->except('document');
        $data['user_id'] = Auth::id();
        $data['reference_no'] = 'production-' . date("Ymd") . '-'. date("his");
        $product = Product::query()->findOrFail($request->product_id);
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
                $document->move('public/documents/production', $documentName);
            }
            else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move('public/documents/production', $documentName);
            }
            $data['document'] = $documentName;
        }
        if(isset($data['created_at']))
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
        else
            $data['created_at'] = date("Y-m-d H:i:s");
        $todayCount = Production::whereDate('created_at', today())->count();
        $data['batch_lot_number'] = 'BATCH-' . date('Ymd') . '-' . str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);
        if (!empty($data['expiry_date'])) {
            $dt = \DateTime::createFromFormat('d-m-Y', $data['expiry_date']) ?: \DateTime::createFromFormat('Y-m-d', $data['expiry_date']);
            $data['expiry_date'] = $dt ? $dt->format('Y-m-d') : null;
        } else {
            $data['expiry_date'] = null;
        }
        $data['production_overhead_type'] = $data['production_overhead_type'] ?? 'fixed';
        $data['production_overhead_cost'] = $data['production_overhead_cost'] ?? 0;
        $data['item'] = count($request->product_qty);
        $data['total_qty'] = $request->total_qty ?? 1;
        $data['product_list'] = implode(",", $data['product_list']);
        $data['qty_list'] = implode(",", $data['product_qty']);
        $data['price_list'] = implode(",", $data['unit_price']);
        $data['wastage_percent'] = implode(",", $data['wastage_percent']);
        $data['production_units_ids'] = implode(",", $data['production_unit_ids']);
        $data['is_raw_material_list'] = implode(",", $request->is_raw_material ?? []);
        $data['variant_list'] = implode(",", $request->variant_id ?? []);
        $data['total_tax'] = 0;
        $lims_production_data = Production::create($data);

        // stock calculate

        // product stock
        $product->qty += $request->total_qty;
        $product->save();

        // wherehouse stock
        $lims_product_warehouse = Product_Warehouse::query()
            ->where([
                ['product_id', $request->product_id],
                ['warehouse_id', $request->warehouse_id]
            ])
            ->latest()
            ->first();
        if($lims_product_warehouse){
            $lims_product_warehouse->qty = $lims_product_warehouse->qty + $request->total_qty;
            $lims_product_warehouse->save();
        }else{
            $lims_product_warehouse = new Product_Warehouse();
            $lims_product_warehouse->product_id = $request->product_id;
            $lims_product_warehouse->warehouse_id = $request->warehouse_id;
            $lims_product_warehouse->qty = $request->total_qty;
            $lims_product_warehouse->save();
        }


        $product_id = $request->product_list;
        $qty = $data['product_qty'];
        $purchase_unit = $data['production_unit_ids'];
        $net_unit_cost = $data['unit_price'];
        $total = $data['subtotal'];
        $is_raw_material_list = $request->is_raw_material ?? [];

        $child_variant_list = $request->variant_id ?? [];
        foreach ($product_id as $index => $child_id) {
            $lims_purchase_unit_data  = Unit::where('id', $purchase_unit[$index])->first();
            if ($lims_purchase_unit_data->operator == '*') {
                $reduced_qty = $qty[$index] * $lims_purchase_unit_data->operation_value;
            } else {
                $reduced_qty = $qty[$index] / $lims_purchase_unit_data->operation_value;
            }
            $is_raw = !empty($is_raw_material_list[$index]) && $is_raw_material_list[$index] == '1';
            if ($is_raw) {
                $rawMaterial = RawMaterial::find($child_id);
                if ($rawMaterial) {
                    $rawMaterial->qty -= $reduced_qty;
                    $rawMaterial->save();
                }
            } else {
                $child_data = Product::find($child_id);
                if (!$child_data) {
                    continue;
                }
                if(count($child_variant_list) && isset($child_variant_list[$index]) && $child_variant_list[$index]) {
                    $child_product_variant_data = ProductVariant::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]]
                    ])->first();
                    if ($child_product_variant_data) {
                        $child_product_variant_data->qty -= $reduced_qty;
                        $child_product_variant_data->save();
                    }
                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]],
                        ['warehouse_id', $lims_production_data->warehouse_id ],
                    ])->first();
                } else {
                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['warehouse_id', $lims_production_data->warehouse_id ],
                    ])->first();
                }
                if($child_warehouse_data){
                    $child_warehouse_data->qty -= $reduced_qty;
                    $child_warehouse_data->save();
                }
                $child_data->qty -= $reduced_qty;
                $child_data->save();
            }
        }
        DB::commit();
        return redirect('manufacturing/productions')->with('message', __('db.Production created successfully'));
        }catch(\Throwable $e){
            DB::rollBack();
            dd($e);
             return redirect('manufacturing/productions')->with('not_permitted', __('db.Something error please try again'));
        }

    }


    public function storebackup(Request $request)
    {
        $data = $request->except('document');
        $data['user_id'] = Auth::id();
        $data['reference_no'] = 'production-' . date("Ymd") . '-'. date("his");
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
                $document->move('public/documents/production', $documentName);
            }
            else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move('public/documents/production', $documentName);
            }
            $data['document'] = $documentName;
        }
        if(isset($data['created_at']))
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
        else
            $data['created_at'] = date("Y-m-d H:i:s");

        $lims_production_data = Production::create($data);
        $product_id = $data['product_id'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $recieved = $data['recieved'];
        $purchase_unit = $data['purchase_unit'];
        $net_unit_cost = $data['net_unit_cost'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];
        $product_production = [];

        foreach ($product_id as $i => $id) {
            $lims_purchase_unit_data  = Unit::where('unit_name', $purchase_unit[$i])->first();

            if ($lims_purchase_unit_data->operator == '*') {
                $quantity = $recieved[$i] * $lims_purchase_unit_data->operation_value;
            } else {
                $quantity = $recieved[$i] / $lims_purchase_unit_data->operation_value;
            }
            $lims_product_data = Product::find($id);

            $child_product_list = explode(",", $lims_product_data->product_list);
            $child_variant_list = explode(",", $lims_product_data->variant_list);
            $child_qty_list = explode(",", $lims_product_data->qty_list);

            if ($lims_purchase_unit_data->operator == '*') {
                $reduced_qty = $qty[$i] * $lims_purchase_unit_data->operation_value;
            }
            else {
                $reduced_qty = $qty[$i] / $lims_purchase_unit_data->operation_value;
            }

            //ducting quantity from child products
            $child_product_list = explode(",", $lims_product_data->product_list);
            if($lims_product_data->variant_list)
                $child_variant_list = explode(",", $lims_product_data->variant_list);
            else
                $child_variant_list = [];

            foreach ($child_product_list as $index => $child_id) {
                $child_data = Product::find($child_id);
                if(count($child_variant_list) && $child_variant_list[$index]) {
                    $child_product_variant_data = ProductVariant::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]]
                    ])->first();

                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]],
                        ['warehouse_id', $lims_production_data->warehouse_id ],
                    ])->first();

                    $child_product_variant_data->qty -= $reduced_qty * $child_qty_list[$index];
                    $child_product_variant_data->save();
                }
                else {
                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['warehouse_id', $lims_production_data->warehouse_id ],
                    ])->first();
                }

                $child_data->qty -= $reduced_qty * $child_qty_list[$index];
                $child_warehouse_data->qty -= $reduced_qty * $child_qty_list[$index];

                $child_data->save();
                $child_warehouse_data->save();
            }

            $lims_product_warehouse_data = Product_Warehouse::where([
                ['product_id', $id],
                ['warehouse_id', $data['warehouse_id'] ],
            ])->first();

            //add quantity to product table
            $lims_product_data->qty = $lims_product_data->qty + $quantity;
            $lims_product_data->save();
            //add quantity to warehouse
            if ($lims_product_warehouse_data) {
                $lims_product_warehouse_data->qty = $lims_product_warehouse_data->qty + $quantity;
            }
            else {
                $lims_product_warehouse_data = new Product_Warehouse();
                $lims_product_warehouse_data->product_id = $id;
                $lims_product_warehouse_data->warehouse_id = $data['warehouse_id'];
                $lims_product_warehouse_data->qty = $quantity;
            }
            $lims_product_warehouse_data->save();

            $product_production['production_id'] = $lims_production_data->id ;
            $product_production['product_id'] = $id;
            $product_production['qty'] = $qty[$i];
            $product_production['recieved'] = $recieved[$i];
            $product_production['purchase_unit_id'] = $lims_purchase_unit_data->id;
            $product_production['net_unit_cost'] = $net_unit_cost[$i];
            $product_production['tax_rate'] = $tax_rate[$i];
            $product_production['tax'] = $tax[$i];
            $product_production['total'] = $total[$i];
            ProductProduction::create($product_production);
        }
        return redirect('manufacturing/productions')->with('message', __('db.Production created successfully'));
    }

    public function productProductionData($id)
    {
        try {
            $lims_product_production_data = Production::where('id', $id)->first();
            if (!$lims_product_production_data) {
                return response()->json(['status' => false, 'message' => 'Production not found'], 404);
            }
            $proudction_units_ids = explode(',', $lims_product_production_data->production_units_ids);
            $product_list = explode(',', $lims_product_production_data->product_list);
            $wastage_percent = explode(',', $lims_product_production_data->wastage_percent);
            $qty_list = explode(',', $lims_product_production_data->qty_list);
            $variant_list = $lims_product_production_data->variant_list ? explode(',', $lims_product_production_data->variant_list) : [];
            $price_list = explode(',', $lims_product_production_data->price_list);

            $production_info = [];
            $production_info['shipping_cost']      = $lims_product_production_data->shipping_cost;
            $production_info['production_cost']    = $lims_product_production_data->production_cost ?? 0;
            $production_info['grand_total']        = $lims_product_production_data->grand_total;
            $production_info['total_qty']          = $lims_product_production_data->total_qty;
            $product_production = [];
            foreach ($product_list as $key => $ingredient_id) {
                $rawMaterial = RawMaterial::find($ingredient_id);
                if ($rawMaterial) {
                    $name = $rawMaterial->name;
                    $code = $rawMaterial->code ?? '';
                } else {
                    $product = Product::find($ingredient_id);
                    if (!$product) {
                        continue;
                    }
                    $name = $product->name;
                    $code = $product->code ?? '';
                    $variant_id = $variant_list[$key] ?? null;
                    if ($variant_id) {
                        $variant = ProductVariant::FindExactProduct($ingredient_id, $variant_id)->first();
                        if ($variant) {
                            $code = $variant->item_code;
                        }
                    }
                }
                $unit = Unit::query()->where('id', $proudction_units_ids[$key] ?? 0)->first();
                if (!$unit) {
                    continue;
                }
                if ($unit->operator == '*') {
                    $subtotal = ($price_list[$key] ?? 0) * ($unit->operation_value * ($qty_list[$key] ?? 1));
                } elseif ($unit->operator == '/') {
                    $subtotal = ($price_list[$key] ?? 0) / $unit->operation_value;
                } else {
                    $subtotal = ($price_list[$key] ?? 0) * ($qty_list[$key] ?? 1);
                }

                $product_production[] = [
                    'id'                => $ingredient_id,
                    'name'              => $name,
                    'code'              => $code,
                    'wastage_percent'   => $wastage_percent[$key] ?? 0,
                    'qty'               => $qty_list[$key] ?? 1,
                    'unit_cost'         => $rawMaterial ? ($rawMaterial->cost ?? 0) : ($product->cost ?? 0),
                    'unit_price'        => $price_list[$key] ?? 0,
                    'subtotal'          => $subtotal,
                    'unit_name'         => $unit->unit_name,
                ];
            }

            return response()->json([
                'status' => true,
                'data' => $product_production,
                'production_info' => $production_info
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something is wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

     public function backupproductProductionData($id)
    {
        try {
            $lims_product_production_data = ProductProduction::where('production_id', $id)->get();
            $product_production = [];
            foreach ($lims_product_production_data as $key => $product_production_data) {
                $product = Product::find($product_production_data->product_id);
                $unit = Unit::find($product_production_data->purchase_unit_id);
                $product_production[0][$key] = $product->name . ' [' . $product->code.']';
                $product_production[1][$key] = $product_production_data->qty;
                $product_production[2][$key] = $product_production_data->recieved;
                $product_production[3][$key] = $unit->unit_code;
                $product_production[4][$key] = $product_production_data->tax;
                $product_production[5][$key] = $product_production_data->tax_rate;
                $product_production[6][$key] = $product_production_data->total;
            }
            return $product_production;
        }
        catch (Exception $e) {
            return 'Something is wrong!';
        }

    }

    public function show($id)
    {
        return view('manufacturing::show');
    }

    public function edit($id)
    {
        $lims_production_data = Production::with('product', 'warehouse')->findOrFail($id);
        if (Auth::user()->role_id > 2) {
            $lims_warehouse_list = Warehouse::where([
                ['is_active', true],
                ['id', Auth::user()->warehouse_id]
            ])->get();
        } else {
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        }
        return view('manufacturing::production.edit', compact('lims_production_data', 'lims_warehouse_list'));
    }

    public function update(Request $request, $id)
    {
        $lims_production_data = Production::findOrFail($id);
        $data = $request->only([
            'created_at', 'warehouse_id', 'production_cost', 'shipping_cost', 'note',
            'production_overhead_type', 'production_overhead_cost', 'expiry_date'
        ]);
        if (isset($data['created_at'])) {
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
        }
        if (!empty($data['expiry_date'])) {
            $dt = \DateTime::createFromFormat('d-m-Y', $data['expiry_date']) ?: \DateTime::createFromFormat('Y-m-d', $data['expiry_date']);
            $data['expiry_date'] = $dt ? $dt->format('Y-m-d') : null;
        } else {
            $data['expiry_date'] = null;
        }
        $overhead = 0;
        $baseTotal = (float)($lims_production_data->total_cost ?? 0);
        if (($data['production_overhead_type'] ?? 'fixed') === 'percent') {
            $overhead = $baseTotal * (float)($data['production_overhead_cost'] ?? 0) / 100;
        } else {
            $overhead = (float)($data['production_overhead_cost'] ?? 0);
        }
        $data['grand_total'] = $baseTotal + (float)($data['production_cost'] ?? 0) + (float)($data['shipping_cost'] ?? 0) + $overhead;
        if ($request->hasFile('document')) {
            $v = Validator::make(
                ['extension' => strtolower($request->document->getClientOriginalExtension())],
                ['extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt']
            );
            if (!$v->fails()) {
                if ($lims_production_data->document && file_exists('public/documents/production/' . $lims_production_data->document)) {
                    unlink('public/documents/production/' . $lims_production_data->document);
                }
                $ext = pathinfo($request->document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis") . '.' . $ext;
                $request->document->move('public/documents/production', $documentName);
                $data['document'] = $documentName;
            }
        }
        if (isset($data['warehouse_id']) && $data['warehouse_id'] != $lims_production_data->warehouse_id) {
            $old_pw = Product_Warehouse::where([
                ['product_id', $lims_production_data->product_id],
                ['warehouse_id', $lims_production_data->warehouse_id]
            ])->first();
            if ($old_pw) {
                $old_pw->qty -= $lims_production_data->total_qty;
                $old_pw->save();
            }
            $new_pw = Product_Warehouse::where([
                ['product_id', $lims_production_data->product_id],
                ['warehouse_id', $data['warehouse_id']]
            ])->first();
            if ($new_pw) {
                $new_pw->qty += $lims_production_data->total_qty;
                $new_pw->save();
            } else {
                Product_Warehouse::create([
                    'product_id' => $lims_production_data->product_id,
                    'warehouse_id' => $data['warehouse_id'],
                    'qty' => $lims_production_data->total_qty
                ]);
            }
        }
        $lims_production_data->update($data);
        return redirect('manufacturing/productions')->with('message', __('db.Production updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $lims_production_data = Production::find($id);
        if (!$lims_production_data) {
            return redirect('manufacturing/productions')->with('not_permitted', __('db.Production not found'));
        }
        DB::beginTransaction();
        try {
            $product = Product::find($lims_production_data->product_id);
            if ($product) {
                $product->qty -= $lims_production_data->total_qty;
                $product->save();
            }
            $lims_product_warehouse = Product_Warehouse::where([
                ['product_id', $lims_production_data->product_id],
                ['warehouse_id', $lims_production_data->warehouse_id]
            ])->first();
            if ($lims_product_warehouse) {
                $lims_product_warehouse->qty -= $lims_production_data->total_qty;
                $lims_product_warehouse->save();
            }
            $product_list = array_filter(explode(",", $lims_production_data->product_list ?? ''));
            $qty_list = explode(",", $lims_production_data->qty_list ?? '');
            $production_units_ids = explode(",", $lims_production_data->production_units_ids ?? '');
            $is_raw_list = explode(",", $lims_production_data->is_raw_material_list ?? '');
            $variant_list = explode(",", $lims_production_data->variant_list ?? '');
            foreach ($product_list as $index => $child_id) {
                $child_id = trim($child_id);
                if (!$child_id) continue;
                $qty_val = isset($qty_list[$index]) ? (float) $qty_list[$index] : 0;
                if ($qty_val <= 0) continue;
                $unit_id = $production_units_ids[$index] ?? null;
                $lims_purchase_unit_data = $unit_id ? Unit::find($unit_id) : null;
                if (!$lims_purchase_unit_data) continue;
                if ($lims_purchase_unit_data->operator == '*') {
                    $reduced_qty = $qty_val * $lims_purchase_unit_data->operation_value;
                } else {
                    $reduced_qty = $qty_val / $lims_purchase_unit_data->operation_value;
                }
                $is_raw = isset($is_raw_list[$index]) && trim($is_raw_list[$index]) == '1';
                if ($is_raw) {
                    $rawMaterial = RawMaterial::find($child_id);
                    if ($rawMaterial) {
                        $rawMaterial->qty += $reduced_qty;
                        $rawMaterial->save();
                    }
                } else {
                    $child_data = Product::find($child_id);
                    if (!$child_data) continue;
                    $variant_id = isset($variant_list[$index]) ? trim($variant_list[$index]) : null;
                    if ($variant_id) {
                        $child_product_variant_data = ProductVariant::where([
                            ['product_id', $child_id],
                            ['variant_id', $variant_id]
                        ])->first();
                        if ($child_product_variant_data) {
                            $child_product_variant_data->qty += $reduced_qty;
                            $child_product_variant_data->save();
                        }
                        $child_warehouse_data = Product_Warehouse::where([
                            ['product_id', $child_id],
                            ['variant_id', $variant_id],
                            ['warehouse_id', $lims_production_data->warehouse_id],
                        ])->first();
                    } else {
                        $child_warehouse_data = Product_Warehouse::where([
                            ['product_id', $child_id],
                            ['warehouse_id', $lims_production_data->warehouse_id],
                        ])->first();
                    }
                    if ($child_warehouse_data) {
                        $child_warehouse_data->qty += $reduced_qty;
                        $child_warehouse_data->save();
                    }
                    $child_data->qty += $reduced_qty;
                    $child_data->save();
                }
            }
            ProductProduction::where('production_id', $id)->delete();
            if ($lims_production_data->document && file_exists('documents/production/' . $lims_production_data->document)) {
                unlink('documents/production/' . $lims_production_data->document);
            }
            $lims_production_data->delete();
            DB::commit();
            return redirect('manufacturing/productions')->with('message', __('db.Production deleted successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect('manufacturing/productions')->with('not_permitted', __('db.Something error please try again'));
        }
    }

    public function productWithoutVariant()
    {
        return Product::ActiveStandard()->select('id', 'name', 'code')
                ->whereNull('is_variant')->get();
    }

    public function productWithVariant()
    {
    return Product::join('product_variants', 'products.id', 'product_variants.product_id')
            ->ActiveStandard()
            ->whereNotNull('is_variant')
            ->select('products.id', 'products.name', 'product_variants.item_code', 'product_variants.qty')
            ->orderBy('position')->get();
    }

    public function getIngredients(Request $request){
         $lims_product_data = Product::where('id', $request->product_id)->first();
        if($lims_product_data->variant_option) {
            $lims_product_data->variant_option = json_decode($lims_product_data->variant_option);
            $lims_product_data->variant_value = json_decode($lims_product_data->variant_value);
        }
        $lims_product_variant_data = $lims_product_data->variant()->orderBy('position')->get();
        if($request->recipe == true){
            $product = view('manufacturing::recipe.ingredients',compact('lims_product_data','lims_product_variant_data'))->render();
        }else{
            $warehouse_id = $request->warehouse_id;
            $product = view('manufacturing::production.ingredients',compact('lims_product_data','lims_product_variant_data','warehouse_id'))->render();
        }

        return response()->json(['ingredients'=> $product]);
    }
}
