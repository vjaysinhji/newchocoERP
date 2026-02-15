<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subcategory\StoreSubcategoryRequest;
use App\Http\Requests\Subcategory\UpdateSubcategoryRequest;
use App\Models\Category;
use App\Models\Subcategory;
use App\Traits\FileHandleTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class SubcategoryController extends Controller
{
    use FileHandleTrait;

    /**
     * Get only product categories (for dropdown and relation).
     */
    protected function getProductCategories()
    {
        return Category::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('type')->orWhere('type', 'product');
            })
            ->orderBy('name')
            ->get();
    }

    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if (!$role->hasPermissionTo('category')) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
        $categories_list = $this->getProductCategories();
        return view('backend.subcategory.index', compact('categories_list'));
    }

    public function subcategoryData(Request $request)
    {
        $columns = [
            0 => 'id',
            1 => 'category_id',
            2 => 'name_english',
            3 => 'name_arabic',
            4 => 'slug',
            5 => 'show_in_menu',
        ];

        $totalData = Subcategory::count();
        $totalFiltered = $totalData;

        $limit = $request->input('length') != -1 ? $request->input('length') : $totalData;
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'asc';

        $orderByCol = (\Schema::hasColumn('subcategories', 'menu_sort_order') && $order === 'show_in_menu') ? 'menu_sort_order' : $order;
        $dirUpper = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        if (empty($request->input('search.value'))) {
            $q = Subcategory::with('category')->offset($start)->limit($limit);
            if ($orderByCol === 'menu_sort_order' && \Schema::hasColumn('subcategories', 'menu_sort_order')) {
                $q->orderByRaw('COALESCE(menu_sort_order, 999999) ' . $dirUpper);
            } else {
                $q->orderBy($orderByCol, $dir);
            }
            $subcategories = $q->get();
        } else {
            $search = $request->input('search.value');
            $subcategories = Subcategory::with('category')
                ->where(function ($q) use ($search) {
                    $q->where('name_english', 'LIKE', "%{$search}%")
                        ->orWhere('name_arabic', 'LIKE', "%{$search}%")
                        ->orWhere('slug', 'LIKE', "%{$search}%")
                        ->orWhereHas('category', function ($cq) use ($search) {
                            $cq->where('name', 'LIKE', "%{$search}%");
                        });
                })
                ->offset($start)
                ->limit($limit);
            if ($orderByCol === 'menu_sort_order' && \Schema::hasColumn('subcategories', 'menu_sort_order')) {
                $subcategories->orderByRaw('COALESCE(menu_sort_order, 999999) ' . $dirUpper);
            } else {
                $subcategories->orderBy($orderByCol, $dir);
            }
            $subcategories = $subcategories->get();
            $totalFiltered = Subcategory::where(function ($q) use ($search) {
                $q->where('name_english', 'LIKE', "%{$search}%")
                    ->orWhere('name_arabic', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%")
                    ->orWhereHas('category', function ($cq) use ($search) {
                        $cq->where('name', 'LIKE', "%{$search}%");
                    });
            })->count();
        }

        $data = [];
        foreach ($subcategories as $key => $sub) {
            $img = $sub->image ? url('images/subcategory', $sub->image) : url('images/zummXD2dvAtI.png');
            $nestedData['id'] = $sub->id;
            $nestedData['key'] = $key;
            $nestedData['name_english'] = '<img src="' . $img . '" height="40" width="40" class="rounded"> ' . e($sub->name_english);
            $nestedData['name_arabic'] = e($sub->name_arabic ?? '—');
            $nestedData['category_name'] = $sub->category ? e($sub->category->name) : '—';
            $nestedData['slug'] = e($sub->slug ?? '—');
            $checked = \Schema::hasColumn('subcategories', 'show_in_menu') && $sub->show_in_menu;
            $nestedData['show_in_menu'] = '<label class="switch mb-0"><input type="checkbox" class="toggle-show-in-menu-sub" data-id="' . $sub->id . '" ' . ($checked ? 'checked' : '') . '><span class="slider round"></span></label>';
            $destroyUrl = route('subcategory.destroy', $sub->id);
            $nestedData['options'] = '<div class="btn-group">
                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . __("db.action") . '
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                    <li>
                        <button type="button" data-id="' . $sub->id . '" class="open-EditSubcategoryDialog btn btn-link" data-toggle="modal" data-target="#editSubcategoryModal"><i class="dripicons-document-edit"></i> ' . __("db.edit") . '</button>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <form method="POST" action="' . e($destroyUrl) . '" class="d-inline" onsubmit="return confirm(\'Delete this subcategory?\');">
                            <input type="hidden" name="_token" value="' . csrf_token() . '">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-link"><i class="dripicons-trash"></i> ' . __("db.delete") . '</button>
                        </form>
                    </li>
                </ul>
            </div>';
            $data[] = $nestedData;
        }

        $json_data = [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data,
        ];
        return response()->json($json_data);
    }

    public function store(StoreSubcategoryRequest $request)
    {
        if (!env('USER_VERIFIED')) {
            return response()->json(['message' => __('db.This feature is disable for demo!')], 403);
        }

        $data = $request->validated();
        $data['slug'] = $request->slug ? Str::slug($request->slug, '-') : Str::slug($request->name_english, '-');

        foreach (['subcate_banner_img', 'image'] as $field) {
            $file = $request->file($field);
            if ($file) {
                $dir = $field === 'subcate_banner_img' ? 'images/subcategory/banner' : 'images/subcategory';
                if (!file_exists(public_path($dir))) {
                    mkdir(public_path($dir), 0755, true);
                }
                $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                $name = date('Ymdhis') . '.' . $ext;
                $file->move(public_path($dir), $name);
                $data[$field] = $name;
            }
        }

        Subcategory::create($data);
        return response()->json(['message' => __('db.Subcategory inserted successfully')]);
    }

    public function edit($id)
    {
        $subcategory = Subcategory::with('category')->find($id);
        if (!$subcategory) {
            return response()->json(['error' => 'Subcategory not found'], 404);
        }
        return response()->json($subcategory);
    }

    public function update(UpdateSubcategoryRequest $request, $id)
    {
        if (!env('USER_VERIFIED')) {
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        }

        $subcategory = Subcategory::find($id);
        if (!$subcategory) {
            return redirect()->back()->with('not_permitted', __('Subcategory not found'));
        }

        $data = $request->except('_token', '_method', 'subcate_banner_img', 'image');
        $data['slug'] = $request->slug ? Str::slug($request->slug, '-') : Str::slug($request->name_english, '-');

        if ($request->hasFile('subcate_banner_img')) {
            $dir = public_path('images/subcategory/banner');
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            $this->fileDelete($dir . '/', $subcategory->subcate_banner_img);
            $file = $request->file('subcate_banner_img');
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $name = date('Ymdhis') . '.' . $ext;
            $file->move($dir, $name);
            $data['subcate_banner_img'] = $name;
        }

        if ($request->hasFile('image')) {
            $dir = public_path('images/subcategory');
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            $this->fileDelete($dir . '/', $subcategory->image);
            $file = $request->file('image');
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $name = date('Ymdhis') . '.' . $ext;
            $file->move($dir, $name);
            $data['image'] = $name;
        }

        $subcategory->update($data);
        return redirect()->route('subcategory.index')->with('message', __('db.Subcategory updated successfully'));
    }

    public function destroy($id)
    {
        $subcategory = Subcategory::find($id);
        if (!$subcategory) {
            return redirect()->back()->with('not_permitted', __('Subcategory not found'));
        }
        $dirBanner = public_path('images/subcategory/banner');
        $dirImg = public_path('images/subcategory');
        $this->fileDelete($dirBanner . '/', $subcategory->subcate_banner_img);
        $this->fileDelete($dirImg . '/', $subcategory->image);
        $subcategory->delete();
        return redirect()->route('subcategory.index')->with('message', __('Subcategory deleted successfully'));
    }

    /** Toggle Show in navbar for a subcategory (same as category). */
    public function toggleShowInMenu(Request $request)
    {
        $request->validate([
            'subcategory_id' => 'required|exists:subcategories,id',
            'show_in_menu' => 'required|in:0,1',
        ]);
        $subcategory = Subcategory::find($request->subcategory_id);
        if (!$subcategory) {
            return response()->json(['success' => false, 'message' => 'Subcategory not found'], 404);
        }
        $showInMenu = (int) $request->show_in_menu;
        $subcategory->show_in_menu = (bool) $showInMenu;
        $subcategory->save();
        if ($showInMenu === 1 && \Schema::hasColumn('subcategories', 'menu_sort_order')) {
            $this->assignMenuSortOrderSubcategory($subcategory);
        }
        return response()->json(['success' => true, 'show_in_menu' => $subcategory->fresh()->show_in_menu]);
    }

    /** Set menu_sort_order to end when enabling show_in_menu (per category). */
    protected function assignMenuSortOrderSubcategory(Subcategory $subcategory)
    {
        if (!\Schema::hasColumn('subcategories', 'menu_sort_order')) {
            return;
        }
        $max = Subcategory::where('category_id', $subcategory->category_id)->max('menu_sort_order');
        $subcategory->menu_sort_order = (int) $max + 1;
        $subcategory->save();
    }

    /** Get subcategories shown in navbar for a category (for arrange modal). */
    public function getMenuSubcategories(Request $request)
    {
        $request->validate(['category_id' => 'required|exists:categories,id']);
        if (!\Schema::hasColumn('subcategories', 'show_in_menu')) {
            return response()->json(['subcategories' => []]);
        }
        $subcategories = Subcategory::where('category_id', $request->category_id)
            ->where('show_in_menu', 1)
            ->orderByRaw('COALESCE(menu_sort_order, 999999) ASC')
            ->orderBy('id')
            ->get(['id', 'name_english', 'slug', 'menu_sort_order']);
        return response()->json(['subcategories' => $subcategories]);
    }

    /** Save navbar menu order for subcategories (order array = subcategory ids). */
    public function saveMenuOrder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:subcategories,id',
        ]);
        if (!\Schema::hasColumn('subcategories', 'menu_sort_order')) {
            return response()->json(['success' => false, 'message' => 'Column not available'], 400);
        }
        foreach ($request->order as $position => $id) {
            Subcategory::where('id', $id)->update(['menu_sort_order' => $position]);
        }
        return response()->json(['success' => true, 'message' => __('Order saved.')]);
    }
}
