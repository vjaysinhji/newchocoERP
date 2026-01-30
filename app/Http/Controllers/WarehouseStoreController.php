<?php

namespace App\Http\Controllers;

use File;
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
use Illuminate\Support\Str;

class WarehouseStoreController extends Controller
{
    use CacheForget;
    use TenantInfo;

    public function indexCategory()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('warehouse-stores-index')) {
            $categories_list = Category::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })
                ->get();
            return view('backend.warehousestore.category.index', compact('categories_list'));
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

        $totalData = Category::where('is_active', true)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'warehouse_store');
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
                            $query->whereNull('type')->orWhere('type', 'warehouse_store');
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
                            $query->whereNull('type')->orWhere('type', 'warehouse_store');
                        })
                        ->offset($start)
                        ->limit($limit)
                        ->orderBy($order,$dir)->get();

            $totalFiltered = Category::where([
                            ['name','LIKE',"%{$search}%"],
                            ['is_active', true]
                        ])
                        ->where(function($query) {
                            $query->whereNull('type')->orWhere('type', 'warehouse_store');
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

                $nestedData['number_of_product'] = Basement::where('category_id', $category->id)->where('is_active', true)->count();
                $nestedData['stock_qty'] = Basement::where('category_id', $category->id)->where('is_active', true)->sum('qty');
                $total_price = Basement::where('category_id', $category->id)->where('is_active', true)->sum(DB::raw('price * qty'));
                $total_cost = Basement::where('category_id', $category->id)->where('is_active', true)->sum(DB::raw('cost * qty'));

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
                                \Form::open(["route" => ["warehouse-stores.category.destroy", $category->id], "method" => "DELETE"] ).'
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
        $lims_category_data['type'] = 'warehouse_store';
        
        $category = Category::create($lims_category_data);
        $this->cacheForget('category_list');
        
        if($request->ajax())
            return $category;
        else
            return redirect()->route('warehouse-stores.category.index')->with('message', __('db.Category inserted successfully'));
    }

    public function editCategory($id)
    {
        $lims_category_data = Category::where('id', $id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'warehouse_store');
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
                $query->whereNull('type')->orWhere('type', 'warehouse_store');
            })
            ->first();

        if(!$lims_category_data) {
            return redirect()->back()->with('not_permitted', __('db.Category not found'));
        }

        $input = $request->except('image','_method','_token','category_id');
        $input['type'] = 'warehouse_store';

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
        
        return redirect()->route('warehouse-stores.category.index')->with('message', __('db.Category updated successfully'));
    }

    public function destroyCategory($id)
    {
        $lims_category_data = Category::where('id', $id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'warehouse_store');
            })
            ->first();
            
        if(!$lims_category_data) {
            return redirect()->back()->with('not_permitted', __('db.Category not found'));
        }
        
        $lims_category_data->is_active = false;
        $lims_basement_data = Basement::where('category_id', $id)->get();
        foreach ($lims_basement_data as $basement_data) {
            $basement_data->is_active = false;
            $basement_data->save();
        }

        if($lims_category_data->image && file_exists(public_path('images/category/' . $lims_category_data->image))) {
            unlink(public_path('images/category/' . $lims_category_data->image));
        }
        if($lims_category_data->image && file_exists(public_path('images/category/large/' . $lims_category_data->image))) {
            unlink(public_path('images/category/large/' . $lims_category_data->image));
        }

        $lims_category_data->save();
        $this->cacheForget('category_list');
        return redirect()->route('warehouse-stores.category.index')->with('not_permitted', __('db.Category deleted successfully'));
    }

    public function deleteCategoryBySelection(Request $request)
    {
        $category_id = $request['categoryIdArray'];
        foreach ($category_id as $id) {
            $lims_category_data = Category::where('id', $id)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })
                ->first();
                
            if($lims_category_data) {
                $lims_basement_data = Basement::where('category_id', $id)->get();
                foreach ($lims_basement_data as $basement_data) {
                    $basement_data->is_active = false;
                    $basement_data->save();
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

    public function indexBrand()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('warehouse-stores-index')) {
            $lims_brand_all = Brand::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })
                ->get();
            return view('backend.warehousestore.brand.index', compact('lims_brand_all'));
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
        $input['type'] = 'warehouse_store';
        
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
            return redirect()->route('warehouse-stores.brand.index')->with('message', __('db.Brand inserted successfully'));
    }

    public function editBrand($id)
    {
        $lims_brand_data = Brand::where('id', $id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'warehouse_store');
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
                $query->whereNull('type')->orWhere('type', 'warehouse_store');
            })
            ->first();
            
        if(!$lims_brand_data) {
            return redirect()->back()->with('not_permitted', __('db.Brand not found'));
        }
        
        $lims_brand_data->title = $request->title;
        $lims_brand_data->type = 'warehouse_store';
        
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
        return redirect()->route('warehouse-stores.brand.index')->with('message', __('db.Brand updated successfully'));
    }

    public function destroyBrand($id)
    {
        $lims_brand_data = Brand::where('id', $id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'warehouse_store');
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
        return redirect()->route('warehouse-stores.brand.index')->with('not_permitted', __('db.Brand deleted successfully!'));
    }

    public function deleteBrandBySelection(Request $request)
    {
        $brand_id = $request['brandIdArray'];
        foreach ($brand_id as $id) {
            $lims_brand_data = Brand::where('id', $id)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
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

    public function indexUnit()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('warehouse-stores-index')) {
            $lims_unit_list = Unit::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
                })
                ->get();
            return view('backend.warehousestore.unit.index', compact('lims_unit_list'));
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
        $input['type'] = 'warehouse_store';
        
        if(!$input['base_unit']){
            $input['operator'] = '*';
            $input['operation_value'] = 1;
        }
        $unit = Unit::create($input);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'unit' => $unit
            ]);
        }

        return redirect()->route('warehouse-stores.unit.index')->with('message', __('db.Unit inserted successfully'));
    }

    public function editUnit($id)
    {
        $lims_unit_data = Unit::where('id', $id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'warehouse_store');
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
        $input['type'] = 'warehouse_store';
        
        if(!$input['base_unit']){
            $input['operator'] = '*';
            $input['operation_value'] = 1;
        }
        
        $lims_unit_data = Unit::where('id', $input['unit_id'])
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'warehouse_store');
            })
            ->first();
            
        if(!$lims_unit_data) {
            return redirect()->back()->with('not_permitted', __('db.Unit not found'));
        }
        
        $lims_unit_data->update($input);
        return redirect()->route('warehouse-stores.unit.index')->with('message', __('db.Unit updated successfully'));
    }

    public function destroyUnit($id)
    {
        $lims_unit_data = Unit::where('id', $id)
            ->where(function($query) {
                $query->whereNull('type')->orWhere('type', 'warehouse_store');
            })
            ->first();
            
        if(!$lims_unit_data) {
            return redirect()->back()->with('not_permitted', __('db.Unit not found'));
        }
        
        $lims_unit_data->is_active = false;
        $lims_unit_data->save();
        return redirect()->route('warehouse-stores.unit.index')->with('not_permitted', __('db.Unit deleted successfully'));
    }

    public function deleteUnitBySelection(Request $request)
    {
        $unit_id = $request['unitIdArray'];
        foreach ($unit_id as $id) {
            $lims_unit_data = Unit::where('id', $id)
                ->where(function($query) {
                    $query->whereNull('type')->orWhere('type', 'warehouse_store');
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
