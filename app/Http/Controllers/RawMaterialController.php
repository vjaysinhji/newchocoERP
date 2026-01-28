<?php

namespace App\Http\Controllers;

use File;
use Exception;
use Keygen\Keygen;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Brand;
use App\Models\RawMaterial;
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
use Illuminate\Support\Str;

class RawMaterialController extends Controller
{
    use CacheForget;
    use TenantInfo;

    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('rawmaterials-index')) {
            // Only load categories, brands, and units for raw materials
            $lims_brand_list = Brand::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })->get();
            $lims_category_list = Category::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })->get();
            $lims_unit_list = Unit::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
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
            $numberOfRawMaterial = DB::table('raw_materials')->where('is_active', true)->count();

            return view('backend.rawmaterial.index', compact('brand_id', 'category_id', 'unit_id', 'tax_id', 'all_permission', 'role_id', 'numberOfRawMaterial', 'lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function rawMaterialData(Request $request)
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
        $order = isset($columns[$orderColumn]) ? 'raw_materials.' . $columns[$orderColumn] : 'raw_materials.name';
        $dir   = $request->input('order.0.dir');

        $baseQuery = RawMaterial::with('category', 'brand', 'unit')
            ->where('raw_materials.is_active', true);

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
                $query->where('raw_materials.name', 'LIKE', "%{$search}%")
                    ->orWhere('raw_materials.code', 'LIKE', "%{$search}%");
            });
            $totalFiltered = $baseQuery->count();
        }

        $raw_materials = $baseQuery->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        foreach ($raw_materials as $raw_material) {
            // Get first image for display
            $imageHtml = '<img src="' . asset('images/rawmaterial/zummXD2dvAtI.png') . '" height="50" width="50">';
            if ($raw_material->image && $raw_material->image != 'zummXD2dvAtI.png') {
                $images = explode(",", $raw_material->image);
                if (!empty($images[0])) {
                    $imageHtml = '<img src="' . asset('images/rawmaterial/' . $images[0]) . '" height="50" width="50">';
                }
            }

            $nestedData['key'] = '';
            $nestedData['id'] = $raw_material->id;
            $nestedData['image'] = $imageHtml;
            $nestedData['name'] = $raw_material->name;
            $nestedData['code'] = $raw_material->code;
            $nestedData['category'] = $raw_material->category ? $raw_material->category->name : 'N/A';
            $nestedData['qty'] = $raw_material->qty ?? 0;
            $nestedData['unit'] = $raw_material->unit ? $raw_material->unit->unit_name : 'N/A';
            $nestedData['cost'] = $raw_material->cost;

            $nestedData['options'] = '<div class="btn-group">
                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . __("db.action") . '
                  <span class="caret"></span>
                  <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">';

            if (in_array("rawmaterials-edit", $request['all_permission']))
                $nestedData['options'] .= \Form::open(["route" => ["rawmaterials.edit", $raw_material->id], "method" => "GET"]) . '
                    <li>
                        <button type="submit" class="btn btn-link"><i class="dripicons-document-edit"></i> ' . __("db.edit") . '</button>
                    </li>' . \Form::close();

            if (in_array("rawmaterials-delete", $request['all_permission']))
                $nestedData['options'] .= \Form::open(["route" => ["rawmaterials.destroy", $raw_material->id], "method" => "DELETE"]) . '
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
        if ($role->hasPermissionTo('rawmaterials-add')) {
            // Only load categories, brands, and units for raw materials
            $lims_brand_list = Brand::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })->get();
            $lims_category_list = Category::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })->get();
            $lims_unit_list = Unit::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $numberOfRawMaterial = RawMaterial::where('is_active', true)->count();

            return view('backend.rawmaterial.create', compact('lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list', 'numberOfRawMaterial'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required|string|max:255',
                'code' => [
                    'required',
                    'max:255',
                    Rule::unique('raw_materials')->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'category_id' => 'required|integer|exists:categories,id',
                'unit_id' => 'required|integer|exists:units,id',
                'cost' => 'required|numeric|min:0',
            ]);

            $data = $request->except('image', 'file');
        $data['name'] = preg_replace('/[\n\r]/', "<br>", htmlspecialchars(trim($data['name']), ENT_QUOTES));
        if (isset($data['name_arabic'])) {
            $data['name_arabic'] = preg_replace('/[\n\r]/', "<br>", htmlspecialchars(trim($data['name_arabic']), ENT_QUOTES));
        }

        $data['product_details'] = str_replace('"', '@', $data['product_details'] ?? '');
        $data['is_active'] = true;
        $data['type'] = $data['type'] ?? 'standard';
        $data['barcode_symbology'] = $data['barcode_symbology'] ?? 'C128';
        $data['price'] = $data['price'] ?? 0;

        $images = $request->file('image');
        $image_names = [];
        if ($images && is_array($images)) {
            if (!file_exists(public_path("images/rawmaterial"))) {
                mkdir(public_path("images/rawmaterial"), 0755, true);
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

                    $image->move(public_path('images/rawmaterial'), $imageName);
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
            if (!file_exists(public_path("rawmaterial/files"))) {
                mkdir(public_path("rawmaterial/files"), 0755, true);
            }
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $fileName = strtotime(date('Y-m-d H:i:s'));
            $fileName = $fileName . '.' . $ext;
            $file->move(public_path('rawmaterial/files'), $fileName);
            $data['file'] = $fileName;
        }

            RawMaterial::create($data);
            \Session::flash('create_message', 'Raw Material created successfully');
            
            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Raw Material created successfully'
                ]);
            }
            
            return redirect()->route('rawmaterials.index')->with('create_message', 'Raw Material created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('db.Failed to create raw material. Please try again') . ': ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('not_permitted', __('db.Failed to create raw material. Please try again'))->withInput();
        }
    }

    public function edit($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('rawmaterials-edit')) {
            // Only load categories, brands, and units for raw materials
            $lims_brand_list = Brand::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })->get();
            $lims_category_list = Category::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })->get();
            $lims_unit_list = Unit::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_rawmaterial_data = RawMaterial::where('id', $id)->first();

            return view('backend.rawmaterial.edit', compact('lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list', 'lims_rawmaterial_data'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function update(Request $request)
    {
        if (!env('USER_VERIFIED')) {
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || str_contains($request->header('Accept', ''), 'application/json')) {
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
                'id' => 'required|integer|exists:raw_materials,id',
                'name' => 'required|string|max:255',
                'code' => [
                    'required',
                    'max:255',
                    Rule::unique('raw_materials')->ignore($request->input('id'))->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'category_id' => 'required|exists:categories,id',
                'unit_id' => 'required|exists:units,id',
                'cost' => 'required|numeric|min:0',
            ], [
                'id.required' => 'Raw Material ID is required',
                'id.exists' => 'Raw Material not found',
                'name.required' => 'Raw Material Name is required',
                'code.required' => 'Code is required',
                'code.unique' => 'This code already exists',
                'category_id.required' => 'Category is required',
                'category_id.exists' => 'Selected category does not exist',
                'unit_id.required' => 'Unit is required',
                'unit_id.exists' => 'Selected unit does not exist',
                'cost.required' => 'Cost is required',
                'cost.numeric' => 'Cost must be a number',
                'cost.min' => 'Cost must be greater than or equal to 0',
            ]);
            
            if ($validator->fails()) {
                DB::rollBack();
                if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || str_contains($request->header('Accept', ''), 'application/json')) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors(),
                        'message' => 'Validation failed'
                    ], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $lims_rawmaterial_data = RawMaterial::findOrFail($request->input('id'));
            $data = $request->except('image', 'file', 'prev_img');
            
            // Convert string IDs to integers for validation
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
            $data['barcode_symbology'] = $data['barcode_symbology'] ?? 'C128';
            $data['type'] = $data['type'] ?? 'standard';
            
            // Preserve price if not provided (required in DB but not in form)
            if (!isset($data['price']) || $data['price'] == '' || $data['price'] == null) {
                $data['price'] = $lims_rawmaterial_data->price ?? 0;
            } else {
                $data['price'] = (float)$data['price'];
            }

            $images = $request->file('image');
            if ($images && is_array($images) && count($images) > 0) {
                if (!file_exists(public_path("images/rawmaterial"))) {
                    mkdir(public_path("images/rawmaterial"), 0755, true);
                }

                if ($lims_rawmaterial_data->image && $lims_rawmaterial_data->image != 'zummXD2dvAtI.png') {
                    $old_images = explode(",", $lims_rawmaterial_data->image);
                    foreach ($old_images as $old_image) {
                        if (file_exists(public_path('images/rawmaterial/' . $old_image))) {
                            unlink(public_path('images/rawmaterial/' . $old_image));
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

                        $image->move(public_path('images/rawmaterial'), $imageName);
                        $image_names[] = $imageName;
                    }
                }
                if (count($image_names) > 0) {
                    $data['image'] = implode(",", $image_names);
                } else {
                    $data['image'] = $lims_rawmaterial_data->image;
                }
            } else {
                $data['image'] = $lims_rawmaterial_data->image;
            }

            $file = $request->file;
            if ($file) {
                if (!file_exists(public_path("rawmaterial/files"))) {
                    mkdir(public_path("rawmaterial/files"), 0755, true);
                }
                if ($lims_rawmaterial_data->file && file_exists(public_path('rawmaterial/files/' . $lims_rawmaterial_data->file))) {
                    unlink(public_path('rawmaterial/files/' . $lims_rawmaterial_data->file));
                }
                $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                $fileName = strtotime(date('Y-m-d H:i:s'));
                $fileName = $fileName . '.' . $ext;
                $file->move(public_path('rawmaterial/files'), $fileName);
                $data['file'] = $fileName;
            } else {
                // Preserve existing file if no new file uploaded
                $data['file'] = $lims_rawmaterial_data->file;
            }

            $lims_rawmaterial_data->update($data);
            DB::commit();
            \Session::flash('edit_message', 'Raw Material updated successfully');
            
            // Check if request is AJAX by checking headers
            $isAjax = $request->ajax() || 
                     $request->wantsJson() || 
                     $request->expectsJson() || 
                     str_contains($request->header('Accept', ''), 'application/json') ||
                     $request->header('X-Requested-With') === 'XMLHttpRequest';
            
            // Always return JSON for AJAX requests
            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => 'Raw Material updated successfully'
                ], 200);
            }
            
            return redirect()->route('rawmaterials.index')->with('edit_message', 'Raw Material updated successfully');
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
            \Log::error('Raw Material Update Error: ' . $e->getMessage());
            \Log::error('Stack Trace: ' . $e->getTraceAsString());
            
            $isAjax = $request->ajax() || 
                     $request->wantsJson() || 
                     $request->expectsJson() || 
                     str_contains($request->header('Accept', ''), 'application/json') ||
                     $request->header('X-Requested-With') === 'XMLHttpRequest';
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => __('db.Failed to update raw material. Please try again') . ': ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('not_permitted', __('db.Failed to update raw material. Please try again'));
        }
    }

    public function deleteBySelection(Request $request)
    {
        $rawmaterial_id = $request['rawmaterialIdArray'];
        foreach ($rawmaterial_id as $id) {
            $lims_rawmaterial_data = RawMaterial::findOrFail($id);
            $lims_rawmaterial_data->is_active = false;
            $lims_rawmaterial_data->save();
        }
        return 'Raw Material deleted successfully!';
    }

    public function destroy($id)
    {
        if (!env('USER_VERIFIED')) {
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        } else {
            $lims_rawmaterial_data = RawMaterial::findOrFail($id);
            $lims_rawmaterial_data->is_active = false;
            $lims_rawmaterial_data->save();
            return redirect()->back()->with('message', __('db.Raw Material deleted successfully'));
        }
    }

    public function generateCode()
    {
        $id = Keygen::numeric(8)->generate();
        return $id;
    }

    // ==================== Raw Material Category Methods ====================
    public function indexCategory()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('rawmaterials-index')) {
            // Get only raw material categories for parent dropdown
            $categories_list = Category::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })
                ->get();
            return view('backend.rawmaterial.category.index', compact('categories_list'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function categoryData(Request $request)
    {
        $columns = array(
            0 =>'id',
            2 =>'name',
            3=> 'parent_id',
            4=> 'is_active',
        );

        // Only get raw material categories
        $totalData = Category::where('is_active', true)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'raw_material');
            })
            ->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        if(empty($request->input('search.value')))
            $categories = Category::offset($start)
                        ->where('is_active', true)
                        ->where(function($query) {
                            $query->whereNull('type')->orWhere('type', 'raw_material');
                        })
                        ->limit($limit)
                        ->orderBy($order,$dir)
                        ->get();
        else
        {
            $search = $request->input('search.value');
            $categories =  Category::where([
                            ['name', 'LIKE', "%{$search}%"],
                            ['is_active', true]
                        ])
                        ->where(function($query) {
                            $query->whereNull('type')->orWhere('type', 'raw_material');
                        })
                        ->offset($start)
                        ->limit($limit)
                        ->orderBy($order,$dir)->get();

            $totalFiltered = Category::where([
                            ['name','LIKE',"%{$search}%"],
                            ['is_active', true]
                        ])
                        ->where(function($query) {
                            $query->whereNull('type')->orWhere('type', 'raw_material');
                        })
                        ->count();
        }
        $data = array();
        if(!empty($categories))
        {
            foreach ($categories as $key=>$category)
            {
                $nestedData['id'] = $category->id;
                $nestedData['key'] = $key;

                if($category->image)
                    $nestedData['name'] = '<img src="'.url('images/category', $category->image).'" height="80" width="80">'.$category->name;
                else
                    $nestedData['name'] = '<img src="'.url('images/zummXD2dvAtI.png').'" height="80" width="80">'.$category->name;

                if($category->parent_id)
                    $nestedData['parent_id'] = Category::find($category->parent_id)->name ?? 'N/A';
                else
                    $nestedData['parent_id'] = "N/A";

                // Count raw materials in this category
                $nestedData['number_of_product'] = RawMaterial::where('category_id', $category->id)->where('is_active', true)->count();
                $nestedData['stock_qty'] = RawMaterial::where('category_id', $category->id)->where('is_active', true)->sum('qty');
                $total_price = RawMaterial::where('category_id', $category->id)->where('is_active', true)->sum(DB::raw('price * qty'));
                $total_cost = RawMaterial::where('category_id', $category->id)->where('is_active', true)->sum(DB::raw('cost * qty'));

                if(config('currency_position') == 'prefix')
                    $nestedData['stock_worth'] = config('currency').' '.$total_price.' / '.config('currency').' '.$total_cost;
                else
                    $nestedData['stock_worth'] = $total_price.' '.config('currency').' / '.$total_cost.' '.config('currency');

                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.__("db.action").'
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" data-id="'.$category->id.'" class="open-EditCategoryDialog btn btn-link" data-toggle="modal" data-target="#editModal" ><i class="dripicons-document-edit"></i> '.__("db.edit").'</button>
                                </li>
                                <li class="divider"></li>'.
                                \Form::open(["route" => ["rawmaterials.category.destroy", $category->id], "method" => "DELETE"] ).'
                                <li>
                                  <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> '.__("db.delete").'</button>
                                </li>'.\Form::close().'
                            </ul>
                        </div>';
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

    public function storeCategory(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
        ]);

        $image = $request->image;
        $lims_category_data = [];
        
        if ($image) {
            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
                $image->move(public_path('images/category'), $imageName);
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                $image->move(public_path('images/category'), $imageName);
            }
            if (!file_exists(public_path('images/category/large/'))) {
                mkdir(public_path('images/category/large/'), 0755, true);
            }
            $manager = new ImageManager(new GdDriver());
            $image = $manager->read(public_path('images/category/' . $imageName));
            $image->resize(600, 750)->save(public_path('images/category/large/' . $imageName));
            $lims_category_data['image'] = $imageName;
        }
        
        $lims_category_data['name'] = preg_replace('/\s+/', ' ', $request->name);
        $lims_category_data['parent_id'] = $request->parent_id ?? null;
        $lims_category_data['is_active'] = true;
        $lims_category_data['type'] = 'raw_material'; // Set type for raw material
        
        $category = Category::create($lims_category_data);
        $this->cacheForget('category_list');
        
        if($request->ajax())
            return $category;
        else
            return redirect()->route('rawmaterials.category.index')->with('message', __('db.Category inserted successfully'));
    }

    public function editCategory($id)
    {
        $lims_category_data = Category::where('id', $id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'raw_material');
            })
            ->first();
        
        if(!$lims_category_data) {
            return response()->json(['error' => 'Category not found'], 404);
        }
        
        if($lims_category_data->parent_id) {
            $lims_parent_data = Category::find($lims_category_data->parent_id);
            if($lims_parent_data){
                $lims_category_data->parent = $lims_parent_data->name;
            }
        }
        return $lims_category_data;
    }

    public function updateCategory(Request $request)
    {
        if(!env('USER_VERIFIED'))
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));

        $this->validate($request, [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
        ]);

        $lims_category_data = Category::where('id', $request->category_id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'raw_material');
            })
            ->first();

        if(!$lims_category_data) {
            return redirect()->back()->with('not_permitted', __('db.Category not found'));
        }

        $input = $request->except('image','_method','_token','category_id');
        $input['type'] = 'raw_material'; // Ensure type is set

        $image = $request->image;
        if ($image) {
            if($lims_category_data->image && file_exists(public_path('images/category/' . $lims_category_data->image))) {
                unlink(public_path('images/category/' . $lims_category_data->image));
            }
            if($lims_category_data->image && file_exists(public_path('images/category/large/' . $lims_category_data->image))) {
                unlink(public_path('images/category/large/' . $lims_category_data->image));
            }

            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
                $image->move(public_path('images/category'), $imageName);
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                $image->move(public_path('images/category'), $imageName);
            }
            if (!file_exists(public_path('images/category/large/'))) {
                mkdir(public_path('images/category/large/'), 0755, true);
            }
            
            $manager = new ImageManager(new GdDriver());
            $image = $manager->read(public_path('images/category/' . $imageName));
            $image->resize(600, 750)->save(public_path('images/category/large/' . $imageName));
             
            $input['image'] = $imageName;
        }

        Category::where('id', $request->category_id)->update($input);
        $this->cacheForget('category_list');
        
        return redirect()->route('rawmaterials.category.index')->with('message', __('db.Category updated successfully'));
    }

    public function destroyCategory($id)
    {
        $lims_category_data = Category::where('id', $id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'raw_material');
            })
            ->first();
            
        if(!$lims_category_data) {
            return redirect()->back()->with('not_permitted', __('db.Category not found'));
        }
        
        $lims_category_data->is_active = false;
        $lims_rawmaterial_data = RawMaterial::where('category_id', $id)->get();
        foreach ($lims_rawmaterial_data as $rawmaterial_data) {
            $rawmaterial_data->is_active = false;
            $rawmaterial_data->save();
        }

        if($lims_category_data->image && file_exists(public_path('images/category/' . $lims_category_data->image))) {
            unlink(public_path('images/category/' . $lims_category_data->image));
        }
        if($lims_category_data->image && file_exists(public_path('images/category/large/' . $lims_category_data->image))) {
            unlink(public_path('images/category/large/' . $lims_category_data->image));
        }

        $lims_category_data->save();
        $this->cacheForget('category_list');
        return redirect()->route('rawmaterials.category.index')->with('not_permitted', __('db.Category deleted successfully'));
    }

    public function deleteCategoryBySelection(Request $request)
    {
        $category_id = $request['categoryIdArray'];
        foreach ($category_id as $id) {
            $lims_category_data = Category::where('id', $id)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })
                ->first();
                
            if($lims_category_data) {
                $lims_rawmaterial_data = RawMaterial::where('category_id', $id)->get();
                foreach ($lims_rawmaterial_data as $rawmaterial_data) {
                    $rawmaterial_data->is_active = false;
                    $rawmaterial_data->save();
                }
                $lims_category_data->is_active = false;
                $lims_category_data->save();

                if($lims_category_data->image && file_exists(public_path('images/category/' . $lims_category_data->image))) {
                    unlink(public_path('images/category/' . $lims_category_data->image));
                }
                if($lims_category_data->image && file_exists(public_path('images/category/large/' . $lims_category_data->image))) {
                    unlink(public_path('images/category/large/' . $lims_category_data->image));
                }
            }
        }
        $this->cacheForget('category_list');
        return 'Category deleted successfully!';
    }

    // ==================== Raw Material Brand Methods ====================
    public function indexBrand()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('rawmaterials-index')) {
            // Only get raw material brands
            $lims_brand_all = Brand::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })
                ->get();
            return view('backend.rawmaterial.brand.index', compact('lims_brand_all'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function storeBrand(Request $request)
    {
        $request->title = preg_replace('/\s+/', ' ', $request->title);
        $this->validate($request, [
            'title' => [
                'max:255',
                    Rule::unique('brands')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
        ]);

        $input = $request->except('image');
        $input['is_active'] = true;
        $input['type'] = 'raw_material'; // Set type for raw material
        
        if(in_array('ecommerce', explode(',',config('addons'))))
            $input['slug'] = Str::slug($request->title, '-');
            
        $image = $request->image;
        if ($image) {
            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
                $image->move(public_path('images/brand'),$imageName);
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                $image->move(public_path('images/brand'),$imageName);
            }
            $input['image'] = $imageName;
        }
        $brand = Brand::create($input);
        $this->cacheForget('brand_list');

        if(isset($input['ajax']))
            return $brand;
        else 
            return redirect()->route('rawmaterials.brand.index')->with('message', __('db.Brand inserted successfully'));
    }

    public function editBrand($id)
    {
        $lims_brand_data = Brand::where('id', $id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'raw_material');
            })
            ->first();
            
        if(!$lims_brand_data) {
            return response()->json(['error' => 'Brand not found'], 404);
        }
        
        return $lims_brand_data;
    }

    public function updateBrand(Request $request, $id)
    {
        $this->validate($request, [
            'title' => [
                'max:255',
                    Rule::unique('brands')->ignore($request->brand_id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
        ]);
        
        $lims_brand_data = Brand::where('id', $request->brand_id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'raw_material');
            })
            ->first();
            
        if(!$lims_brand_data) {
            return redirect()->back()->with('not_permitted', __('db.Brand not found'));
        }
        
        $lims_brand_data->title = $request->title;
        $lims_brand_data->type = 'raw_material'; // Ensure type is set
        
        if(in_array('ecommerce', explode(',',config('addons')))) {
            $lims_brand_data->page_title = $request->page_title;
            $lims_brand_data->short_description = $request->short_description;
        }
        $image = $request->image;
        if ($image) {
            if($lims_brand_data->image && file_exists(public_path('images/brand/' . $lims_brand_data->image))) {
                unlink(public_path('images/brand/' . $lims_brand_data->image));
            }
            
            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
                $image->move(public_path('images/brand'),$imageName);
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                $image->move(public_path('images/brand'),$imageName);
            }
            $lims_brand_data->image = $imageName;
        }
        $lims_brand_data->save();
        $this->cacheForget('brand_list');
        return redirect()->route('rawmaterials.brand.index')->with('message', __('db.Brand updated successfully'));
    }

    public function destroyBrand($id)
    {
        $lims_brand_data = Brand::where('id', $id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'raw_material');
            })
            ->first();
            
        if(!$lims_brand_data) {
            return redirect()->back()->with('not_permitted', __('db.Brand not found'));
        }
        
        $lims_brand_data->is_active = false;
        if($lims_brand_data->image && file_exists(public_path('images/brand/' . $lims_brand_data->image))) {
            unlink(public_path('images/brand/' . $lims_brand_data->image));
        }
        $lims_brand_data->save();
        $this->cacheForget('brand_list');
        return redirect()->route('rawmaterials.brand.index')->with('not_permitted', __('db.Brand deleted successfully!'));
    }

    public function deleteBrandBySelection(Request $request)
    {
        $brand_id = $request['brandIdArray'];
        foreach ($brand_id as $id) {
            $lims_brand_data = Brand::where('id', $id)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })
                ->first();
                
            if($lims_brand_data) {
                if($lims_brand_data->image && file_exists(public_path('images/brand/' . $lims_brand_data->image))) {
                    unlink(public_path('images/brand/' . $lims_brand_data->image));
                }
                $lims_brand_data->is_active = false;
                $lims_brand_data->save();
            }
        }
        $this->cacheForget('brand_list');
        return 'Brand deleted successfully!';
    }

    // ==================== Raw Material Unit Methods ====================
    public function indexUnit()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('rawmaterials-index')) {
            // Only get raw material units
            $lims_unit_list = Unit::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })
                ->get();
            return view('backend.rawmaterial.unit.index', compact('lims_unit_list'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function storeUnit(Request $request)
    {
        $this->validate($request, [
            'unit_code' => [
                'max:255',
                    Rule::unique('units')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'unit_name' => [
                'max:255',
                    Rule::unique('units')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ]
        ]);
        
        $input = $request->all();
        $input['is_active'] = true;
        $input['type'] = 'raw_material'; // Set type for raw material
        
        if(!$input['base_unit']){
            $input['operator'] = '*';
            $input['operation_value'] = 1;
        }
        $unit = Unit::create($input);

        // Handle AJAX request
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'unit' => $unit
            ]);
        }

        return redirect()->route('rawmaterials.unit.index')->with('message', __('db.Unit inserted successfully'));
    }

    public function editUnit($id)
    {
        $lims_unit_data = Unit::where('id', $id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'raw_material');
            })
            ->first();
            
        if(!$lims_unit_data) {
            return response()->json(['error' => 'Unit not found'], 404);
        }
        
        return $lims_unit_data;
    }

    public function updateUnit(Request $request, $id)
    {
        $this->validate($request, [
            'unit_code' => [
                'max:255',
                    Rule::unique('units')->ignore($request->unit_id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'unit_name' => [
                'max:255',
                    Rule::unique('units')->ignore($request->unit_id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ]
        ]);

        $input = $request->all();
        $input['type'] = 'raw_material'; // Ensure type is set
        
        if(!$input['base_unit']){
            $input['operator'] = '*';
            $input['operation_value'] = 1;
        }
        
        $lims_unit_data = Unit::where('id', $input['unit_id'])
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'raw_material');
            })
            ->first();
            
        if(!$lims_unit_data) {
            return redirect()->back()->with('not_permitted', __('db.Unit not found'));
        }
        
        $lims_unit_data->update($input);
        return redirect()->route('rawmaterials.unit.index')->with('message', __('db.Unit updated successfully'));
    }

    public function destroyUnit($id)
    {
        $lims_unit_data = Unit::where('id', $id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'raw_material');
            })
            ->first();
            
        if(!$lims_unit_data) {
            return redirect()->back()->with('not_permitted', __('db.Unit not found'));
        }
        
        $lims_unit_data->is_active = false;
        $lims_unit_data->save();
        return redirect()->route('rawmaterials.unit.index')->with('not_permitted', __('db.Unit deleted successfully'));
    }

    public function deleteUnitBySelection(Request $request)
    {
        $unit_id = $request['unitIdArray'];
        foreach ($unit_id as $id) {
            $lims_unit_data = Unit::where('id', $id)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'raw_material');
                })
                ->first();
                
            if($lims_unit_data) {
                $lims_unit_data->is_active = false;
                $lims_unit_data->save();
            }
        }
        return 'Unit deleted successfully!';
    }
}
