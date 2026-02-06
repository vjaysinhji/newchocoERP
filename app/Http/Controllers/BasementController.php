<?php

namespace App\Http\Controllers;

use File;
use Exception;
use Keygen\Keygen;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Brand;
use App\Models\Basement;
use App\Models\Category;
use App\Traits\TenantInfo;
use App\Traits\CacheForget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Illuminate\Validation\Rule;

class BasementController extends Controller
{
    use CacheForget;
    use TenantInfo;

    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('warehouse-stores-index')) {
            $lims_brand_list = Brand::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })->get();
            $lims_category_list = Category::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })->get();
            $lims_unit_list = Unit::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })->get();
            $lims_tax_list = Tax::where('is_active', true)->get();

            $brand_id = 0;
            $category_id = 0;
            $unit_id = 0;
            $tax_id = 0;

            if ($request->input('brand_id')) $brand_id = $request->input('brand_id');
            if ($request->input('category_id')) $category_id = $request->input('category_id');
            if ($request->input('unit_id')) $unit_id = $request->input('unit_id');
            if ($request->input('tax_id')) $tax_id = $request->input('tax_id');

            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';
            $role_id = $role->id;
            $numberOfBasement = DB::table('basements')->where('is_active', true)->count();

            return view('backend.basement.index', compact('brand_id', 'category_id', 'unit_id', 'tax_id', 'all_permission', 'role_id', 'numberOfBasement', 'lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function basementData(Request $request)
    {
        $columns = [
            1 => 'name',
            2 => 'code',
            3 => 'category_id',
            4 => 'qty',
            5 => 'unit_id',
            6 => 'cost',
        ];

        $filtered_data = [
            'brand_id'     => $request->input('brand_id'),
            'category_id'  => $request->input('category_id'),
            'unit_id'      => $request->input('unit_id'),
            'tax_id'       => $request->input('tax_id'),
        ];

        $limit = ($request->input('length') != -1) ? $request->input('length') : null;
        $start = $request->input('start');
        $orderColumn = $request->input('order.0.column');
        $order = isset($columns[$orderColumn]) ? 'basements.' . $columns[$orderColumn] : 'basements.name';
        $dir   = $request->input('order.0.dir');

        $baseQuery = Basement::with('category', 'brand', 'unit')
            ->where('basements.is_active', true);

        if ($filtered_data['brand_id'] != '0') {
            $baseQuery->where('brand_id', $filtered_data['brand_id']);
        }
        if ($filtered_data['category_id'] != '0') {
            $baseQuery->where('category_id', $filtered_data['category_id']);
        }
        if ($filtered_data['unit_id'] != '0') {
            $baseQuery->where('unit_id', $filtered_data['unit_id']);
        }
        if ($filtered_data['tax_id'] != '0') {
            $baseQuery->where('tax_id', $filtered_data['tax_id']);
        }

        $totalData = $baseQuery->count();
        $totalFiltered = $totalData;

        if ($request->input('search.value')) {
            $search = $request->input('search.value');
            $baseQuery->where(function ($query) use ($search) {
                $query->where('basements.name', 'LIKE', "%{$search}%")
                    ->orWhere('basements.code', 'LIKE', "%{$search}%");
            });
            $totalFiltered = $baseQuery->count();
        }

        $basements = $baseQuery->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        foreach ($basements as $basement) {
            $nestedData['key'] = '';
            $nestedData['id'] = $basement->id;
            $nestedData['name'] = $basement->name;
            $nestedData['code'] = $basement->code;
            $nestedData['category'] = $basement->category ? $basement->category->name : 'N/A';
            $nestedData['qty'] = $basement->qty ?? 0;
            $nestedData['unit'] = $basement->unit ? $basement->unit->unit_name : 'N/A';
            $nestedData['cost'] = $basement->cost;

            $nestedData['options'] = '<div class="btn-group">
                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . __("db.action") . '
                  <span class="caret"></span>
                  <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">';

            if (in_array("warehouse-stores-edit", $request['all_permission']))
                $nestedData['options'] .= \Form::open(["route" => ["warehouse-stores.edit", $basement->id], "method" => "GET"]) . '
                    <li>
                        <button type="submit" class="btn btn-link"><i class="dripicons-document-edit"></i> ' . __("db.edit") . '</button>
                    </li>' . \Form::close();

            if (in_array("warehouse-stores-delete", $request['all_permission']))
                $nestedData['options'] .= \Form::open(["route" => ["warehouse-stores.destroy", $basement->id], "method" => "DELETE"]) . '
                    <li>
                    <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="fa fa-trash"></i> ' . __("db.delete") . '</button>
                    </li>' . \Form::close() . '
                </ul>
            </div>';

            $data[] = $nestedData;
        }

        return response()->json([
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        ]);
    }

    public function create()
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('warehouse-stores-add')) {
            $lims_brand_list = Brand::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })->get();
            $lims_category_list = Category::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })->get();
            $lims_unit_list = Unit::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $numberOfBasement = Basement::where('is_active', true)->count();

            return view('backend.basement.create', compact('lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list', 'numberOfBasement'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function store(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => [
                    'required',
                    'max:255',
                    Rule::unique('basements')->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
            ]);
            
            if ($validator->fails()) {
                if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || str_contains($request->header('Accept', ''), 'application/json')) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors(),
                        'message' => 'Validation failed'
                    ], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $data = $request->except('image', 'file');
            $data['name'] = preg_replace('/[\n\r]/', "<br>", htmlspecialchars(trim($data['name']), ENT_QUOTES));
            if (isset($data['name_arabic'])) {
                $data['name_arabic'] = preg_replace('/[\n\r]/', "<br>", htmlspecialchars(trim($data['name_arabic']), ENT_QUOTES));
            }

            $data['product_details'] = str_replace('"', '@', $data['product_details'] ?? '');
            $data['is_active'] = true;
            $data['type'] = $data['type'] ?? 'standard';
            $data['barcode_symbology'] = $data['barcode_symbology'] ?? 'UPCE';
            $data['price'] = $data['price'] ?? 0;

            $images = $request->file('image');
            $image_names = [];
            if ($images && is_array($images)) {
                if (!file_exists(public_path("images/basement"))) {
                    mkdir(public_path("images/basement"), 0755, true);
                }

                foreach ($images as $key => $image) {
                    if ($image && $image->isValid()) {
                        $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                        $imageName = date("Ymdhis") . ($key + 1);

                        if (!config('database.connections.saleprosaas_landlord')) {
                            $imageName = $imageName . '.' . $ext;
                        } else {
                            $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                        }

                        $image->move(public_path('images/basement'), $imageName);
                        $image_names[] = $imageName;
                    }
                }
                if (count($image_names) > 0) {
                    $data['image'] = implode(",", $image_names);
                } else {
                    $data['image'] = 'zummXD2dvAtI.png';
                }
            } else {
                $data['image'] = 'zummXD2dvAtI.png';
            }

            $file = $request->file;
            if ($file) {
                if (!file_exists(public_path("basement/files"))) {
                    mkdir(public_path("basement/files"), 0755, true);
                }
                $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                $fileName = strtotime(date('Y-m-d H:i:s'));
                $fileName = $fileName . '.' . $ext;
                $file->move(public_path('basement/files'), $fileName);
                $data['file'] = $fileName;
            }

            Basement::create($data);
            \Session::flash('create_message', 'Warehouse Store created successfully');
            
            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || str_contains($request->header('Accept', ''), 'application/json')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Warehouse Store created successfully'
                ], 200);
            }
            
            return redirect()->route('warehouse-stores.index')->with('create_message', 'Warehouse Store created successfully');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || str_contains($request->header('Accept', ''), 'application/json')) {
                return response()->json([
                    'success' => false,
                    'message' => __('db.Failed to create warehouse store. Please try again') . ': ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('not_permitted', __('db.Failed to create warehouse store. Please try again'))->withInput();
        }
    }

    public function edit($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('warehouse-stores-edit')) {
            $lims_brand_list = Brand::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })->get();
            $lims_category_list = Category::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })->get();
            $lims_unit_list = Unit::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_basement_data = Basement::where('id', $id)->first();

            return view('backend.basement.edit', compact('lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list', 'lims_basement_data'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function update(Request $request)
    {
        if (!env('USER_VERIFIED')) {
            $isAjax = $request->ajax() || 
                     $request->wantsJson() || 
                     $request->expectsJson() || 
                     str_contains($request->header('Accept', ''), 'application/json') ||
                     $request->header('X-Requested-With') === 'XMLHttpRequest';
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => __('db.This feature is disable for demo!')
                ], 403);
            }
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        }

        DB::beginTransaction();
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required|integer|exists:basements,id',
                'name' => 'required|string|max:255',
                'code' => [
                    'required',
                    'max:255',
                    Rule::unique('basements')->ignore($request->input('id'))->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
            ]);
            
            if ($validator->fails()) {
                DB::rollBack();
                $isAjax = $request->ajax() || 
                         $request->wantsJson() || 
                         $request->expectsJson() || 
                         str_contains($request->header('Accept', ''), 'application/json') ||
                         $request->header('X-Requested-With') === 'XMLHttpRequest';
                
                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors(),
                        'message' => 'Validation failed'
                    ], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $lims_basement_data = Basement::findOrFail($request->input('id'));
            $data = $request->except('image', 'file', 'prev_img');
            
            // Convert string IDs to integers
            if (isset($data['category_id'])) {
                $data['category_id'] = (int)$data['category_id'];
            }
            if (isset($data['unit_id'])) {
                $data['unit_id'] = (int)$data['unit_id'];
            }
            if (isset($data['cost'])) {
                $data['cost'] = (float)$data['cost'];
            }
            
            // Clean and process name field
            $data['name'] = preg_replace('/[\n\r]/', "<br>", htmlspecialchars(trim($data['name']), ENT_QUOTES));
            if (isset($data['name_arabic'])) {
                $data['name_arabic'] = preg_replace('/[\n\r]/', "<br>", htmlspecialchars(trim($data['name_arabic']), ENT_QUOTES));
            }

            $data['product_details'] = str_replace('"', '@', $data['product_details'] ?? '');
            
            // Ensure required fields have defaults
            $data['barcode_symbology'] = $data['barcode_symbology'] ?? 'UPCE';
            $data['type'] = $data['type'] ?? 'standard';
            
            // Preserve price if not provided (required in DB but not in form)
            if (!isset($data['price']) || $data['price'] == '' || $data['price'] == null) {
                $data['price'] = $lims_basement_data->price ?? 0;
            } else {
                $data['price'] = (float)$data['price'];
            }

            $images = $request->file('image');
            if ($images && is_array($images) && count($images) > 0) {
                if (!file_exists(public_path("images/basement"))) {
                    mkdir(public_path("images/basement"), 0755, true);
                }

                if ($lims_basement_data->image && $lims_basement_data->image != 'zummXD2dvAtI.png') {
                    $old_images = explode(",", $lims_basement_data->image);
                    foreach ($old_images as $old_image) {
                        if (file_exists(public_path('images/basement/' . $old_image))) {
                            unlink(public_path('images/basement/' . $old_image));
                        }
                    }
                }

                $image_names = [];
                foreach ($images as $key => $image) {
                    if ($image && $image->isValid()) {
                        $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                        $imageName = date("Ymdhis") . ($key + 1);

                        if (!config('database.connections.saleprosaas_landlord')) {
                            $imageName = $imageName . '.' . $ext;
                        } else {
                            $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                        }

                        $image->move(public_path('images/basement'), $imageName);
                        $image_names[] = $imageName;
                    }
                }
                if (count($image_names) > 0) {
                    $data['image'] = implode(",", $image_names);
                } else {
                    $data['image'] = $lims_basement_data->image;
                }
            } else {
                $data['image'] = $lims_basement_data->image;
            }

            $file = $request->file;
            if ($file) {
                if (!file_exists(public_path("basement/files"))) {
                    mkdir(public_path("basement/files"), 0755, true);
                }
                if ($lims_basement_data->file && file_exists(public_path('basement/files/' . $lims_basement_data->file))) {
                    unlink(public_path('basement/files/' . $lims_basement_data->file));
                }
                $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                $fileName = strtotime(date('Y-m-d H:i:s'));
                $fileName = $fileName . '.' . $ext;
                $file->move(public_path('basement/files'), $fileName);
                $data['file'] = $fileName;
            } else {
                // Preserve existing file if no new file uploaded
                $data['file'] = $lims_basement_data->file;
            }

            $lims_basement_data->update($data);
            DB::commit();
            \Session::flash('edit_message', 'Warehouse Store updated successfully');
            
            // Check if request is AJAX
            $isAjax = $request->ajax() || 
                     $request->wantsJson() || 
                     $request->expectsJson() || 
                     str_contains($request->header('Accept', ''), 'application/json') ||
                     $request->header('X-Requested-With') === 'XMLHttpRequest';
            
            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => 'Warehouse Store updated successfully'
                ], 200);
            }
            
            return redirect()->route('warehouse-stores.index')->with('edit_message', 'Warehouse Store updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            $isAjax = $request->ajax() || 
                     $request->wantsJson() || 
                     $request->expectsJson() || 
                     str_contains($request->header('Accept', ''), 'application/json') ||
                     $request->header('X-Requested-With') === 'XMLHttpRequest';
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Warehouse Store Update Error: ' . $e->getMessage());
            \Log::error('Stack Trace: ' . $e->getTraceAsString());
            
            $isAjax = $request->ajax() || 
                     $request->wantsJson() || 
                     $request->expectsJson() || 
                     str_contains($request->header('Accept', ''), 'application/json') ||
                     $request->header('X-Requested-With') === 'XMLHttpRequest';
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => __('db.Failed to update warehouse store. Please try again') . ': ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('not_permitted', __('db.Failed to update warehouse store. Please try again'));
        }
    }

    public function deleteBySelection(Request $request)
    {
        $basement_id = $request['basementIdArray'];
        foreach ($basement_id as $id) {
            $lims_basement_data = Basement::findOrFail($id);
            $lims_basement_data->is_active = false;
            $lims_basement_data->save();
        }
        return 'Warehouse Store deleted successfully!';
    }

    public function destroy($id)
    {
        if (!env('USER_VERIFIED')) {
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        } else {
            $lims_basement_data = Basement::findOrFail($id);
            $lims_basement_data->is_active = false;
            $lims_basement_data->save();
            return redirect()->back()->with('message', __('db.Warehouse Store deleted successfully'));
        }
    }

    public function generateCode()
    {
        $id = Keygen::numeric(8)->generate();
        return $id;
    }
}
