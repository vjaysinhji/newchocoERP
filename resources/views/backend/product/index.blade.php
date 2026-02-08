@extends('backend.layout.main')
@section('content')

<style type="text/css">
    .btn-icon i {
        margin-right: 5px
    }

    .top-fields {
        margin-top: 10px;
        position: relative;
    }

    .top-fields label {
        background: #FFF;
        font-size: 11px;
        font-weight: 600;
        margin-left: 10px;
        padding: 0 3px;
        position: absolute;
        top: -8px;
        z-index: 9;
    }

    .top-fields input {
        font-size: 13px;
        height: 45px
    }

    /* ✅ Quantity column width fix */
    #product-data-table th:nth-child(7),
    #product-data-table td:nth-child(7) {
        min-width: 130px;
        text-align: center;
        white-space: nowrap;
    }
</style>


    <x-success-message key="create_message" />
    <x-success-message key="import_message" />
    <x-error-message key="not_permitted" />
    <x-error-message key="message" />

    <section>
        <div class="container-fluid">

            @can('products-add')
                @if(isset($product_index_type) && $product_index_type == 'single')
                    <a href="{{ route('products.single.create') }}" class="btn btn-info add-product-btn btn-icon"><i class="dripicons-plus"></i> {{ __('db.Add Single Product') }}</a>
                @elseif(isset($product_index_type) && $product_index_type == 'combo')
                    <a href="{{ route('products.combo.create') }}" class="btn btn-info add-product-btn btn-icon"><i class="dripicons-plus"></i> {{ __('db.Add Combo Product') }}</a>
                @else
                    <a href="{{ route('products.create') }}" class="btn btn-info add-product-btn btn-icon"><i class="dripicons-plus"></i> {{ __('db.add_product') }}</a>
                @endif
            @endcan
            @can('products-import')
                <a href="#" data-toggle="modal" data-target="#importProduct"
                    class="btn btn-primary add-product-btn btn-icon"><i class="dripicons-copy"></i>
                    {{ __('db.import_product') }}</a>
            @endcan

            @can('products-edit')
                @if (in_array('ecommerce', explode(',', $general_setting->modules)))
                    <a href="{{ route('product.allProductInStock') }}" class="btn btn-dark add-product-btn btn-icon"><i
                            class="dripicons-stack"></i> {{ __('db.All Product In Stock') }}</a>
                    <a href="{{ route('product.showAllProductOnline') }}" class="btn btn-dark add-product-btn btn-icon"><i
                            class="dripicons-wifi"></i> {{ __('db.Show All Product Online') }}</a>
                @endif
            @endcan
            <button type="button" class="btn btn-warning btn-icon" id="toggle-filter">
                <i class="dripicons-experiment"></i> {{ __('db.Filter Products') }}
            </button>

            <div class="card mt-3 mb-2">
                <div class="card-body" id="filter-card" style="display: none;">
                    <div class="row">
                        <div class="col-md-3 @if (\Auth::user()->role_id > 2) {{ 'd-none' }} @endif">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Warehouse') }}</label>
                                <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control"
                                    data-live-search="true" data-live-search-style="begins">
                                    <option value="0">{{ __('db.All Warehouse') }}</option>
                                    @foreach ($lims_warehouse_list as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if(!isset($product_index_type) || $product_index_type == 'all')
                        <div class="col-md-3">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Product Type') }}</label>
                                <select name="product_type" required class="form-control selectpicker" id="product_type"
                                    data-live-search="true" data-live-search-style="begins">
                                    <option value="all" selected>All Types</option>
                                    <option value="standard">Single Product</option>
                                    <option value="combo">Combo Product</option>
                                    <option value="digital">Digital</option>
                                    <option value="service">Service</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Brand') }}</label>
                                <select name="brand_id" required class="form-control selectpicker" id="brand_id"
                                    data-live-search="true" data-live-search-style="begins">
                                    <option value="0" selected>All Brands</option>
                                    @foreach ($lims_brand_list as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <div class="form-group top-fields">
                                <label>{{ __('db.category') }}</label>
                                <select name="category_id" required class="form-control selectpicker" id="category_id"
                                    data-live-search="true" data-live-search-style="begins">
                                    <option value="0" selected>All Categories</option>
                                    @foreach ($lims_category_list as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Unit') }}</label>
                                <select name="unit_id" required class="form-control selectpicker" id="unit_id"
                                    data-live-search="true" data-live-search-style="begins">
                                    <option value="0" selected>All Unit</option>
                                    @foreach ($lims_unit_list as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->unit_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Tax') }}</label>
                                <select name="tax_id" required class="form-control selectpicker" id="tax_id"
                                    data-live-search="true" data-live-search-style="begins">
                                    <option value="0" selected>All Tax</option>
                                    @foreach ($lims_tax_list as $tax)
                                        <option value="{{ $tax->id }}">{{ $tax->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if(!isset($product_index_type) || $product_index_type == 'all')
                        <div class="col-md-3">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Product with') }}</label>
                                <select name="imeiorvariant" required class="form-control selectpicker" id="imeiorvariant">
                                    <option value="0" selected>Select IMEI/Variant</option>
                                    <option value="imei">IMEI</option>
                                    <option value="variant">Variant</option>
                                </select>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Stock') }}</label>
                                <select name="stock_filter" required class="form-control selectpicker" id="stock_filter">
                                    <option value="all" selected>All</option>
                                    <option value="with">With Stock</option>
                                    <option value="without">Without Stock</option>
                                </select>
                            </div>
                        </div>
                        <div id="filter-loading" class="col-12 text-center my-2" style="display:none;">
                            <span class="spinner-border text-primary spinner-border-sm" role="status"></span>
                            <span>Loading results...</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table id="product-data-table" class="table pt-0" style="width: 100%">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>{{ __('db.product') }}</th>
                        <th>Arabic Name</th>
                        <th>{{ __('db.Code') }}</th>
                        @if(!isset($product_index_type) || $product_index_type == 'all')
                        <th>{{ __('db.Brand') }}</th>
                        @endif
                        <th>{{ __('db.category') }}</th>
                        <th>{{ __('db.Quantity') }}</th>
                        <th>{{ __('db.Unit') }}</th>
                        <th>{{ __('db.Price') }}</th>
                        @if ($role_id <= 2)
                            <th>{{ __('db.Cost') }}</th>
                            <th>{{ __('db.Stock Worth') . '(' . __('db.Price') . '/' . __('db.Cost') . ')' }}</th>
                        @endif
                        @foreach ($custom_fields as $fieldName)
                            <th>{{ $fieldName }}</th>
                        @endforeach
                        <th class="not-exported">{{ __('db.action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </section>

    <div id="importProduct" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                {!! Form::open(['route' => 'product.import', 'method' => 'post', 'files' => true]) !!}
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">Import Product</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                            aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                </div>
                <div class="modal-body">
                    <p class="italic">
                        <small>{{ __('db.The field labels marked with * are required input fields') }}.</small>
                    </p>
                    <p>{{ __('db.The correct column order is') }} (image, name*, name_arabic, code*, type*, brand,
                        category*, unit_code*, cost*, profit_margin(%), price, product_details, variant_name, item_code,
                        additional_price) {{ __('db.and you must follow this') }}.</p>
                    <p>If you provide profit_margin, then price will be calculated based on profit_margin: <strong>price =
                            cost * (1 + profit_margin / 100)</strong></p>
                    <p>{{ __('db.To display Image it must be stored in') }} images/product {{ __('db.directory') }}.
                        {{ __('db.Image name must be same as product name') }}</p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('db.Upload CSV File') }} *</label>
                                {{ Form::file('file', ['class' => 'form-control', 'required']) }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label> {{ __('db.Sample File') }}</label>
                                <a href="sample_file/sample_products.csv" class="btn btn-info btn-block btn-md"><i
                                        class="dripicons-download"></i> {{ __('db.Download') }}</a>
                            </div>
                        </div>
                    </div>
                    {{ Form::submit('Submit', ['class' => 'btn btn-primary']) }}
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>

    <div id="product-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{ __('db.Product Details') }}</h5>
                    <button id="print-btn" type="button" class="btn btn-default btn-sm ml-3"><i
                            class="dripicons-print"></i> {{ __('db.Print') }}</button>
                    <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close"><span
                            aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5" id="slider-content"></div>
                        <div class="col-md-5 offset-1" id="product-content"></div>
                        <div class="col-md-12 mt-2" id="product-warehouse-section">
                            <h5>{{ __('db.Warehouse Quantity') }}</h5>
                            <table class="table table-bordered table-hover product-warehouse-list">
                                <thead>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-12 mt-2 d-none" id="product-variant-section">
                            <h5>{{ __('db.Product Variant Information') }}</h5>
                            <table class="table table-bordered table-hover product-variant-list">
                                <thead>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-5 mt-2" id="product-variant-warehouse-section">
                            <h5>{{ __('db.Warehouse quantity of product variants') }}</h5>
                            <table class="table table-bordered table-hover product-variant-warehouse-list">
                                <thead>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <h5 id="combo-header"></h5>
                    <table class="table table-bordered table-hover item-list">
                        <thead>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(isset($product_index_type) && $product_index_type == 'combo')
    <style>
        #combo-assemble-modal .modal-dialog { max-width: 1000px; }
        #combo-assemble-modal .modal-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; padding: 1rem 1.25rem; }
        #combo-assemble-modal .modal-header .modal-title { font-weight: 600; font-size: 1.1rem; }
        #combo-assemble-modal .modal-header .close { color: #fff; opacity: 0.9; text-shadow: none; }
        #combo-assemble-modal .modal-body { padding: 1.5rem 1.25rem; }
        #combo-assemble-modal .combo-assemble-section { background: #f8f9fa; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; }
        #combo-assemble-modal .combo-assemble-section label { font-weight: 600; font-size: 0.85rem; color: #495057; margin-bottom: 0.4rem; }
        #combo-assemble-modal .qty-stepper { display: flex; align-items: center; border: 1px solid #ced4da; border-radius: 6px; overflow: hidden; }
        #combo-assemble-modal .qty-stepper .btn-qty { width: 38px; height: 38px; border: none; background: #e9ecef; color: #495057; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0; }
        #combo-assemble-modal .qty-stepper .btn-qty:hover { background: #dee2e6; }
        #combo-assemble-modal .qty-stepper input { width: 60px; text-align: center; border: none; border-left: 1px solid #ced4da; border-right: 1px solid #ced4da; font-weight: 600; }
        #combo-assemble-modal .ingredients-table { border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        #combo-assemble-modal .ingredients-table thead { background: #495057; color: #fff; }
        #combo-assemble-modal .ingredients-table thead th { border: none; padding: 0.75rem 1rem; font-size: 0.85rem; font-weight: 600; }
        #combo-assemble-modal .ingredients-table tbody td { padding: 0.65rem 1rem; vertical-align: middle; }
        #combo-assemble-modal .modal-footer-custom { padding-top: 1rem; border-top: 1px solid #e9ecef; display: flex; gap: 0.5rem; justify-content: flex-end; }
    </style>
    <div id="combo-assemble-modal" tabindex="-1" role="dialog" class="modal fade text-left">
        <div role="document" class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="dripicons-plus"></i> {{ __('db.Assemble Combo') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span><i class="dripicons-cross"></i></span></button>
                </div>
                <div class="modal-body">
                    <div id="combo-assemble-loading" class="text-center py-5">
                        <span class="spinner-border text-primary"></span>
                        <p class="mt-2 mb-0 text-muted">{{ __('db.Loading') }}...</p>
                    </div>
                    <div id="combo-assemble-form" style="display:none;">
                        <input type="hidden" id="combo_assemble_product_id" value="">
                        <div class="combo-assemble-section">
                            <p class="mb-3 font-weight-bold text-dark" id="combo-assemble-name"></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <label>{{ __('db.Combo Warehouse') }} *</label>
                                    <select id="combo_warehouse_id" class="form-control selectpicker" data-live-search="true" required>
                                        <option value="">{{ __('db.Select Warehouse') }}</option>
                                        @foreach($lims_warehouse_list ?? [] as $w)
                                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>{{ __('db.Quantity') }} *</label>
                                    <div class="qty-stepper">
                                        <button type="button" class="btn-qty" id="combo_qty_minus" title="Decrease"><i class="dripicons-minus"></i></button>
                                        <input type="number" id="combo_assemble_qty" min="0.001" step="any" value="1" required>
                                        <button type="button" class="btn-qty" id="combo_qty_plus" title="Increase"><i class="dripicons-plus"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label>{{ __('db.Expiry Date') }}</label>
                                    <input type="text" id="combo_assemble_expiry" class="form-control date" placeholder="dd-mm-yyyy" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <label class="font-weight-bold text-dark mb-2">{{ __('db.Ingredient List') }}</label>
                        <div class="table-responsive ingredients-table-wrapper">
                            <table class="table table-bordered ingredients-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('db.product') }}</th>
                                        <th>{{ __('db.Warehouse') }}</th>
                                        <th>{{ __('db.Quantity') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="combo-assemble-ingredients-tbody">
                                </tbody>
                            </table>
                        </div>
                        <div id="combo-assemble-error" class="alert alert-danger mt-2" style="display:none;"></div>
                        <div class="modal-footer-custom">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('db.Cancel') }}</button>
                            <button type="button" id="combo-assemble-submit" class="btn btn-primary"><i class="dripicons-plus"></i> {{ __('db.Assemble') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection
@push('scripts')
    <script>
        $("ul#product").siblings('a').attr('aria-expanded', 'true');
        $("ul#product").addClass("show");

        @if (config('database.connections.saleprosaas_landlord'))
            if (localStorage.getItem("message")) {
                alert(localStorage.getItem("message"));
                localStorage.removeItem("message");
            }

            numberOfProduct = <?php echo json_encode($numberOfProduct); ?>;
            $.ajax({
                type: 'GET',
                async: false,
                url: '{{ route('package.fetchData', $general_setting->package_id) }}',
                success: function(data) {
                    if (data['number_of_product'] > 0 && data['number_of_product'] <= numberOfProduct) {
                        $("a.add-product-btn").addClass('d-none');
                    }
                }
            });
        @endif

        function confirmDelete() {
            if (confirm("Are you sure want to delete?")) {
                return true;
            }
            return false;
        }

        var role_id = <?php echo json_encode($role_id); ?>;
        var product_index_type = <?php echo json_encode($product_index_type ?? 'all'); ?>;
        if (product_index_type === 'single') $("ul#product #product-single-list-menu").addClass("active");
        else if (product_index_type === 'combo') $("ul#product #product-combo-list-menu").addClass("active");
        else $("ul#product #product-single-list-menu").addClass("active");
        var columns = [{"data": "key"}, {"data": "name"}, {"data": "name_arabic"}, {"data": "code"}];
        if (product_index_type === 'all') {
            columns.push({"data": "brand"});
        }
        columns.push({"data": "category"}, {"data": "qty"}, {"data": "unit"}, {"data": "price"});
        if (role_id <= 2) {
            columns.push({
                "data": "cost"
            });
            columns.push({
                "data": "stock_worth"
            });
        }
        var field_name = <?php echo json_encode($field_name); ?>;
        for (i = 0; i < field_name.length; i++) {
            columns.push({
                "data": field_name[i]
            });
        }
        columns.push({
            "data": "options"
        });

        var warehouse = [];
        var variant = [];
        var qty = [];
        var htmltext;
        var slidertext;
        var product_id = [];
        var all_permission = <?php echo json_encode($all_permission); ?>;
        var user_verified = <?php echo json_encode(env('USER_VERIFIED')); ?>;
        var logoUrl = <?php echo json_encode(url('logo', $general_setting->site_logo)); ?>;
        var warehouse_id = <?php echo json_encode($warehouse_id); ?>;
        var product_type = <?php echo json_encode($product_type); ?>;
        var brand_id = <?php echo json_encode($brand_id); ?>;
        var category_id = <?php echo json_encode($category_id); ?>;
        var unit_id = <?php echo json_encode($unit_id); ?>;
        var tax_id = <?php echo json_encode($tax_id); ?>;
        var imeiorvariant = <?php echo json_encode($imeiorvariant); ?>;
        var stock_filter = <?php echo json_encode($stock_filter); ?>;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $("#warehouse_id").val(warehouse_id);
        $("#product_type").val(product_type);
        $("#brand_id").val(brand_id);
        $("#category_id").val(category_id);
        $("#unit_id").val(unit_id);
        $("#tax_id").val(tax_id);
        $("#imeiorvariant").val(imeiorvariant);
        $("#stock_filter").val(stock_filter);

        $("#select_all").on("change", function() {
            if ($(this).is(':checked')) {
                $("tbody input[type='checkbox']").prop('checked', true);
            } else {
                $("tbody input[type='checkbox']").prop('checked', false);
            }
        });

        $(document).on("click", "tr.product-link td:not(:first-child, :last-child)", function() {
            productDetails($(this).parent().data('product'), $(this).parent().data('imagedata'));
        });

        $(document).on("click", ".view", function() {
            var product = $(this).parent().parent().parent().parent().parent().data('product');
            var imagedata = $(this).parent().parent().parent().parent().parent().data('imagedata');
            // console.log(product);
            productDetails(product, imagedata);
        });

        @if(isset($product_index_type) && $product_index_type == 'combo')
        $(document).on("click", ".btn-assemble-combo", function(e) {
            e.preventDefault();
            e.stopPropagation();
            var product = $(this).closest('tr').data('product');
            if (!product || !product[13]) return;
            var productId = product[13];
            $('#combo-assemble-modal').modal('show');
            $('#combo-assemble-loading').show();
            $('#combo-assemble-form').hide();
            $('#combo-assemble-error').hide();
            $('#combo_assemble_product_id').val(productId);
            $.get("{{ url('products/combo-ingredients') }}/" + productId)
                .done(function(data) {
                    $('#combo-assemble-name').text((data.combo.name || '') + ' [' + (data.combo.code || '') + ']');
                    $('#combo_assemble_qty').val(1);
                    var tbody = $('#combo-assemble-ingredients-tbody');
                    tbody.empty();
                    data.ingredients.forEach(function(ing, i) {
                        var whOpts = '<option value="">{{ __("db.Select Warehouse") }}</option>';
                        (ing.warehouse_qtys || []).forEach(function(w) {
                            whOpts += '<option value="' + w.id + '" data-qty="' + w.qty + '">' + w.name + ' ({{ __("db.Available") }}: ' + w.qty + ')</option>';
                        });
                        var baseQty = parseFloat(ing.qty) || 1;
                        var unit = ing.unit_name || '';
                        var badge = (ing.item_type === 'warehouse_store') ? ' <span class="badge badge-info">{{ __("db.Warehouse Store") }}</span>' : ' <span class="badge badge-primary">{{ __("db.Single Product") }}</span>';
                        var qtyCell = '<td><div class="input-group" style="max-width: 140px">' +
                            '<input type="number" class="form-control ingredient-qty-input" name="ingredient_qty[' + ing.index + ']" data-qty="' + baseQty + '" value="' + baseQty + '" step="any" min="0" placeholder="Qty">' +
                            '<div class="input-group-append"><span class="input-group-text">' + unit + '</span></div></div></td>';
                        var row = '<tr data-index="' + ing.index + '" data-item-type="' + (ing.item_type || 'single') + '">' +
                            '<td>' + ing.name + ' [' + (ing.code || '') + ']' + badge + '</td>' +
                            '<td><select class="form-control ingredient-warehouse-select" name="ingredient_warehouse_id[' + ing.index + ']" required>' + whOpts + '</select></td>' +
                            qtyCell +
                            '</tr>';
                        tbody.append(row);
                    });
                    $('#combo_warehouse_id').selectpicker('refresh');
                    // Do NOT apply selectpicker to ingredient-warehouse-select so .val() is reliable in modal
                    $('#combo-assemble-loading').hide();
                    $('#combo-assemble-form').show();
                    $('#combo_assemble_expiry').val('');
                    if ($('#combo_assemble_expiry').hasClass('hasDatepicker')) {
                        $('#combo_assemble_expiry').datepicker('destroy');
                    }
                    $('#combo_assemble_expiry').datepicker({ format: 'dd-mm-yyyy', autoclose: true, todayHighlight: true });
                    updateComboIngredientQtys();
                })
                .fail(function(xhr) {
                    $('#combo-assemble-loading').hide();
                    $('#combo-assemble-form').show();
                    $('#combo-assemble-error').text(xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : '{{ __("db.Error loading data") }}').show();
                });
        });
        function updateComboIngredientQtys() {
            var qty = parseFloat($('#combo_assemble_qty').val()) || 1;
            $('#combo-assemble-ingredients-tbody tr').each(function() {
                var baseQty = parseFloat($(this).find('.ingredient-qty-input').data('qty')) || 0;
                $(this).find('.ingredient-qty-input').val((baseQty * qty).toFixed(4));
            });
        }
        $('#combo_assemble_qty').on('input change', function() { updateComboIngredientQtys(); });
        $('#combo_qty_plus').on('click', function() {
            var inp = $('#combo_assemble_qty');
            var v = parseFloat(inp.val()) || 0;
            inp.val(v + 1);
            updateComboIngredientQtys();
        });
        $('#combo_qty_minus').on('click', function() {
            var inp = $('#combo_assemble_qty');
            var v = Math.max(0.001, (parseFloat(inp.val()) || 1) - 1);
            inp.val(v);
            updateComboIngredientQtys();
        });
        // Helper: get value from select (bootstrap-select in modal may not sync to native .value)
        function getSelectpickerVal($sel) {
            if (!$sel || !$sel.length) return '';
            var el = $sel[0];
            try {
                var v = null;
                if (typeof $sel.selectpicker === 'function') v = $sel.selectpicker('val');
                if (v === null || v === undefined) v = $sel.val();
                if (Array.isArray(v)) v = v.length ? v[0] : '';
                if ((v === null || v === undefined || v === '') && el && el.options && el.options[el.selectedIndex])
                    v = el.options[el.selectedIndex].value;
                return (v !== undefined && v !== null && v !== '') ? String(v).trim() : '';
            } catch (e) {
                if (el && el.options && el.selectedIndex >= 0 && el.options[el.selectedIndex])
                    return String(el.options[el.selectedIndex].value || '').trim();
                return ($sel.val() !== undefined && $sel.val() !== null && $sel.val() !== '') ? String($sel.val()).trim() : '';
            }
        }
        $('#combo-assemble-submit').on('click', function() {
            var productId = $('#combo_assemble_product_id').val();
            var $comboWh = $('#combo-assemble-modal #combo_warehouse_id');
            var comboWh = getSelectpickerVal($comboWh);
            if (!comboWh && $comboWh.length) comboWh = ($comboWh[0].value !== undefined ? String($comboWh[0].value).trim() : '') || getSelectpickerVal($comboWh);
            var qtyNum = parseFloat($('#combo_assemble_qty').val());
            var qty = isNaN(qtyNum) ? 0 : qtyNum;
            var ingWh = {};
            var ingQty = {};
            $('#combo-assemble-modal #combo-assemble-ingredients-tbody .ingredient-warehouse-select').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    var idx = name.match(/\[(\d+)\]/);
                    if (idx) {
                        var v = (this.value !== undefined && this.value !== null) ? String(this.value).trim() : getSelectpickerVal($(this));
                        ingWh[idx[1]] = v || '';
                    }
                }
            });
            $('#combo-assemble-modal #combo-assemble-ingredients-tbody .ingredient-qty-input').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    var idx = name.match(/\[(\d+)\]/);
                    if (idx) ingQty[idx[1]] = $(this).val();
                }
            });
            if (!comboWh) {
                $('#combo-assemble-error').text('{{ __("db.Please select combo warehouse") }}').show();
                return;
            }
            if (qty <= 0) {
                $('#combo-assemble-error').text('{{ __("db.Please enter a valid quantity") }}').show();
                return;
            }
            var allSelected = true;
            $('#combo-assemble-modal #combo-assemble-ingredients-tbody .ingredient-warehouse-select').each(function() {
                var v = (this.value !== undefined && this.value !== null) ? String(this.value).trim() : getSelectpickerVal($(this));
                if (!v) allSelected = false;
            });
            if (!allSelected) {
                $('#combo-assemble-error').text('{{ __("db.Please select warehouse for all ingredients") }}').show();
                return;
            }
            $('#combo-assemble-error').hide();
            var btn = $(this);
            btn.prop('disabled', true);
            var expiryVal = $('#combo_assemble_expiry').val() || '';
            
            // Build data object with proper array structure for Laravel
            var dataObj = {
                _token: '{{ csrf_token() }}',
                combo_product_id: productId,
                combo_warehouse_id: comboWh,
                quantity: qty
            };
            if (expiryVal) dataObj.expiry_date = expiryVal;
            
            // Convert objects to arrays with proper indexing for Laravel (only non-empty values)
            dataObj.ingredient_warehouse_id = {};
            dataObj.ingredient_qty = {};
            var hasWarehouses = false;
            for (var idx in ingWh) {
                if (ingWh.hasOwnProperty(idx) && ingWh[idx] && String(ingWh[idx]).trim() !== '') {
                    dataObj.ingredient_warehouse_id[idx] = String(ingWh[idx]).trim();
                    hasWarehouses = true;
                }
            }
            for (var idx in ingQty) {
                if (ingQty.hasOwnProperty(idx) && ingQty[idx] && String(ingQty[idx]).trim() !== '') {
                    dataObj.ingredient_qty[idx] = String(ingQty[idx]).trim();
                }
            }
            
            // Ensure we have at least one warehouse selected
            if (!hasWarehouses || Object.keys(dataObj.ingredient_warehouse_id).length === 0) {
                $('#combo-assemble-error').text('{{ __("db.Please select warehouse for all ingredients") }}').show();
                btn.prop('disabled', false);
                return;
            }
            
            // Serialize manually to ensure proper array format: ingredient_warehouse_id[0]=value
            var dataString = '_token=' + encodeURIComponent(dataObj._token) +
                '&combo_product_id=' + encodeURIComponent(dataObj.combo_product_id) +
                '&combo_warehouse_id=' + encodeURIComponent(dataObj.combo_warehouse_id) +
                '&quantity=' + encodeURIComponent(dataObj.quantity);
            if (dataObj.expiry_date) {
                dataString += '&expiry_date=' + encodeURIComponent(dataObj.expiry_date);
            }
            for (var idx in dataObj.ingredient_warehouse_id) {
                if (dataObj.ingredient_warehouse_id.hasOwnProperty(idx)) {
                    dataString += '&ingredient_warehouse_id[' + idx + ']=' + encodeURIComponent(dataObj.ingredient_warehouse_id[idx]);
                }
            }
            for (var idx in dataObj.ingredient_qty) {
                if (dataObj.ingredient_qty.hasOwnProperty(idx)) {
                    dataString += '&ingredient_qty[' + idx + ']=' + encodeURIComponent(dataObj.ingredient_qty[idx]);
                }
            }
            
            $.ajax({
                url: "{{ route('product.combo.assemble') }}",
                method: 'POST',
                contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                data: dataString
            }).done(function(res) {
                if (res.success) {
                    $('#combo-assemble-modal').modal('hide');
                    $('#product-data-table').DataTable().ajax.reload();
                    alert(res.message || '{{ __("db.Combo assembled successfully") }}');
                } else {
                    $('#combo-assemble-error').text(res.message || '{{ __("db.Error") }}').show();
                }
            }).fail(function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __("db.Error") }}';
                $('#combo-assemble-error').text(msg).show();
            }).always(function() {
                btn.prop('disabled', false);
            });
        });
        $('#combo-assemble-modal').on('shown.bs.modal', function() {
            $('.selectpicker').selectpicker('refresh');
        });
        $('#combo-assemble-modal').on('hidden.bs.modal', function() {
            $('#combo-assemble-ingredients-tbody').empty();
        });
        @endif

        $("#print-btn").on("click", function() {
            var divToPrint = document.getElementById('product-details');
            var newWin = window.open('', 'Print-Window');
            newWin.document.open();
            newWin.document.write(
                '<link rel="stylesheet" href="<?php echo asset('vendor/bootstrap/css/bootstrap.min.css'); ?>" type="text/css"><style type="text/css">@media print {.modal-dialog { max-width: 1000px;} }</style><body onload="window.print()">' +
                divToPrint.innerHTML + '</body>');
            newWin.document.close();
            setTimeout(function() {
                newWin.close();
            }, 10);
        });

        function productDetails(product, imagedata) {
            product[12] = product[12].replace(/@/g, '"');
            htmltext = slidertext = '';

            var typeLabel = (product[0] || '').toString().replace(/"/g, '').trim();
            if (typeLabel === 'standard') typeLabel = 'Single Product';
            if (typeLabel === 'combo') typeLabel = 'Combo Product';
            htmltext = '<p>{{ __('db.Type') }}: ' + typeLabel +
                '</p><p>{{ __('db.name') }}: ' + product[1] + (product[2] && product[2].trim ? (' / ' + product[2]) : '') +
                '</p><p>{{ __('db.Code') }}: ' + product[3] +
                (product_index_type === 'all' ? '</p><p>{{ __('db.Brand') }}: ' + product[4] + '</p><p>{{ __('db.category') }}: ' + product[5] : '</p><p>{{ __('db.category') }}: ' + product[5]) +
                '</p><p>{{ __('db.Quantity') }}: ' + (product[18] != null ? String(product[18]).replace(/"/g, '').trim() : '0') +
                '</p><p>{{ __('db.Unit') }}: ' + product[6] +
                (role_id < 3 ? '</p><p>{{ __('db.Cost') }}: ' + product[7] : '') +
                '</p><p>{{ __('db.Price') }}: ' + product[8] +
                '</p><p>{{ __('db.Tax') }}: ' + product[9] +
                '</p><p>{{ __('db.Tax Method') }} : ' + product[10] +
                '</p><p>{{ __('db.Alert Quantity') }} : ' + product[11] +
                '</p><p>{{ __('db.Product Details') }}: </p>' + product[12];

            if (product[19]) {
                var product_image = product[19].split(",");
                if (product_image.length > 1) {
                    slidertext =
                        '<div id="product-img-slider" class="carousel slide" data-ride="carousel"><div class="carousel-inner">';
                    for (var i = 0; i < product_image.length; i++) {
                        if (!i)
                            slidertext += '<div class="carousel-item active"><img src="images/product/' + product_image[i] +
                            '" height="300" width="100%"></div>';
                        else
                            slidertext += '<div class="carousel-item"><img src="images/product/' + product_image[i] +
                            '" height="300" width="100%"></div>';
                    }
                    slidertext +=
                        '</div><a class="carousel-control-prev" href="#product-img-slider" data-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="sr-only">Previous</span></a><a class="carousel-control-next" href="#product-img-slider" data-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="sr-only">Next</span></a></div>';
                } else {
                    slidertext = '<img src="images/product/' + product[19] + '" height="300" width="100%">';
                }
            } else {
                slidertext = '<img src="images/product/zummXD2dvAtI.png" height="300" width="100%">';
            }
            $("#combo-header").text('');
            $("table.item-list thead").remove();
            $("table.item-list tbody").remove();
            $("table.product-warehouse-list thead").remove();
            $("table.product-warehouse-list tbody").remove();
            $(".product-variant-list thead").remove();
            $(".product-variant-list tbody").remove();
            $(".product-variant-warehouse-list thead").remove();
            $(".product-variant-warehouse-list tbody").remove();
            $("#product-warehouse-section").addClass('d-none');
            $("#product-variant-section").addClass('d-none');
            $("#product-variant-warehouse-section").addClass('d-none');
            if (product[0] == 'combo') {
                $("#combo-header").text('{{ __('db.Combo Products') }}');
                product_list = (product[14] || '').toString().replace(/"/g, '').split(",");
                variant_list = (product[15] || '').toString().replace(/"/g, '').split(",");
                qty_list = (product[16] || '').toString().replace(/"/g, '').split(",");
                price_list = (product[17] || '').toString().replace(/"/g, '').split(",");
                combo_unit = (product[21] || '').toString().replace(/"/g, '').split(",");
                wastage_percent = (product[22] || '').toString().replace(/"/g, '').split(",");
                $(".item-list thead").remove();
                $(".item-list tbody").remove();
                var newHead = $("<thead>");
                var newBody = $("<tbody>");
                var newRow = $("<tr>");
                newRow.append(
                    '<th>{{ __('db.product') }}</th><th>{{ __('db.Wastage Percent') }}</th><th>{{ __('db.Quantity') }}</th><th>{{ __('db.Price') }}</th>'
                );
                newHead.append(newRow);
                // console.log(product)
                $(product_list).each(function(i) {
                    if (!variant_list[i])
                        variant_list[i] = 0;
                    $.get('products/getdata/' + product_list[i] + '/' + variant_list[i], function(data) {
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td>' + data['name'] + ' [' + data['code'] + ']</td>';
                        cols += '<td>' + wastage_percent[i] + '</td>';
                        cols += '<td>' + qty_list[i] + '(' + combo_unit[i] + ')</td>';
                        cols += '<td>' + price_list[i] + '</td>';

                        newRow.append(cols);
                        newBody.append(newRow);
                    });
                });

                $("table.item-list").append(newHead);
                $("table.item-list").append(newBody);
            }
            if (product[0] == 'standard' || product[0] == 'combo') {
                if (product[20]) {
                    $.get('products/variant-data/' + product[13], function(variantData) {
                        if (variantData && variantData.length > 0) {
                            var newHead = $("<thead>");
                            var newBody = $("<tbody>");
                            var newRow = $("<tr>");
                            newRow.append(
                                '<th>{{ __('db.Variant') }}</th><th>{{ __('db.Item Code') }}</th><th>{{ __('db.Additional Cost') }}</th><th>{{ __('db.Additional Price') }}</th><th>{{ __('db.Qty') }}</th>'
                            );
                            newHead.append(newRow);
                            $.each(variantData, function(i) {
                                var newRow = $("<tr>");
                                var cols = '';
                                cols += '<td>' + variantData[i]['name'] + '</td>';
                                cols += '<td>' + variantData[i]['item_code'] + '</td>';
                                if (variantData[i]['additional_cost'])
                                    cols += '<td>' + variantData[i]['additional_cost'] + '</td>';
                                else
                                    cols += '<td>0</td>';
                                if (variantData[i]['additional_price'])
                                    cols += '<td>' + variantData[i]['additional_price'] + '</td>';
                                else
                                    cols += '<td>0</td>';
                                cols += '<td>' + variantData[i]['qty'] + '</td>';

                                newRow.append(cols);
                                newBody.append(newRow);
                            });
                            $("table.product-variant-list").append(newHead);
                            $("table.product-variant-list").append(newBody);
                            $("#product-variant-section").removeClass('d-none');
                        } else {
                            $("#product-variant-section").addClass('d-none');
                        }
                    }).fail(function() {
                        $("#product-variant-section").addClass('d-none');
                    });
                } else {
                    $("#product-variant-section").addClass('d-none');
                }
            } else {
                $("#product-variant-section").addClass('d-none');
            }

            // Always fetch warehouse data for all users and all product types (includes combo-assembled batches)
            var productId = product[13]; // Product ID is at index 13
            let url = "{{ route('product.warehouse', ['id' => '__ID__']) }}".replace('__ID__', productId);
            $.get(url, function(data) {
                if (data && data.product_warehouse && data.product_warehouse[0] && data.product_warehouse[0]
                    .length != 0) {
                    warehouse = data.product_warehouse[0];
                    qty = data.product_warehouse[1];
                    batch = data.product_warehouse[2];
                    expired_date = data.product_warehouse[3];
                    unit_cost = data.product_warehouse[4];
                    var newHead = $("<thead>");
                    var newBody = $("<tbody>"); 
                    var newRow = $("<tr>");
                    newRow.append(
                        '<th>{{ __('db.Warehouse') }}</th><th>{{ __('db.Batch No') }}</th><th>{{ __('db.Expired Date') }}</th><th>{{ __('db.Quantity') }}</th><th>{{ __('db.Unit Cost') }}</th>'
                    );
                    newHead.append(newRow);
                    $.each(warehouse, function(index) {
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td>' + warehouse[index] + '</td>';
                        cols += '<td>' + batch[index] + '</td>';
                        cols += '<td>' + expired_date[index] + '</td>';
                        cols += '<td>' + qty[index] + '</td>';
                        cols += '<td>' + (unit_cost[index] || 'N/A') + '</td>';
                        newRow.append(cols);
                        newBody.append(newRow);
                    });
                    $("table.product-warehouse-list").append(newHead);
                    $("table.product-warehouse-list").append(newBody);
                    // console.log(productQty);
                    $("#product-warehouse-section").removeClass('d-none');
                } else {
                    // Show empty message if no warehouse data
                    var newHead = $("<thead>");
                    var newBody = $("<tbody>");
                    var newRow = $("<tr>");
                    newRow.append(
                        '<th>{{ __('db.Warehouse') }}</th><th>{{ __('db.Batch No') }}</th><th>{{ __('db.Expired Date') }}</th><th>{{ __('db.Quantity') }}</th><th>{{ __('db.Unit Cost') }}</th>'
                    );
                    newHead.append(newRow);
                    var emptyRow = $("<tr>");
                    emptyRow.append('<td colspan="5" class="text-center">No warehouse data available</td>');
                    newBody.append(emptyRow);
                    $("table.product-warehouse-list").append(newHead);
                    $("table.product-warehouse-list").append(newBody);
                    $("#product-warehouse-section").removeClass('d-none');
                }
                if (data && data.product_variant_warehouse && data.product_variant_warehouse[0] && data
                    .product_variant_warehouse[0].length != 0) {
                    warehouse = data.product_variant_warehouse[0];
                    variant = data.product_variant_warehouse[1];
                    qty = data.product_variant_warehouse[2];
                    var newHead = $("<thead>");
                    var newBody = $("<tbody>");
                    var newRow = $("<tr>");
                    newRow.append(
                        '<th>{{ __('db.Warehouse') }}</th><th>{{ __('db.Variant') }}</th><th>{{ __('db.Quantity') }}</th>'
                    );
                    newHead.append(newRow);
                    $.each(warehouse, function(index) {
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td>' + warehouse[index] + '</td>';
                        cols += '<td>' + variant[index] + '</td>';
                        cols += '<td>' + qty[index] + '</td>';

                        newRow.append(cols);
                        newBody.append(newRow);
                    });
                    $("table.product-variant-warehouse-list").append(newHead);
                    $("table.product-variant-warehouse-list").append(newBody);
                    $("#product-variant-warehouse-section").removeClass('d-none');
                }
            }).fail(function(xhr, status, error) {
                console.error('Error fetching warehouse data:', error);
                // Show error message
                var newHead = $("<thead>");
                var newBody = $("<tbody>");
                var newRow = $("<tr>");
                newRow.append(
                    '<th>{{ __('db.Warehouse') }}</th><th>{{ __('db.Batch No') }}</th><th>{{ __('db.Expired Date') }}</th><th>{{ __('db.Quantity') }}</th><th>{{ __('db.Unit Cost') }}</th>'
                );
                newHead.append(newRow);
                var errorRow = $("<tr>");
                errorRow.append(
                    '<td colspan="5" class="text-center text-danger">Error loading warehouse data</td>');
                newBody.append(errorRow);
                $("table.product-warehouse-list").append(newHead);
                $("table.product-warehouse-list").append(newBody);
                $("#product-warehouse-section").removeClass('d-none');
            });

            $('#product-content').html(htmltext);
            $('#slider-content').html(slidertext);
            $('#product-details').modal('show');
            $('#product-img-slider').carousel(0);
        }

        $('#toggle-filter').on('click', function() {
            $('#filter-card').slideToggle('slow');
        });

        let buttons = [];
        @can('product_export')
            buttons.push([{
                    extend: 'pdf',
                    text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible',
                        stripHtml: false
                    },
                    customize: function(doc) {
                        for (var i = 1; i < doc.content[1].table.body.length; i++) {
                            if (doc.content[1].table.body[i][0].text.indexOf('<img src=') !== -1) {
                                var imagehtml = doc.content[1].table.body[i][0].text;
                                var regex = /<img.*?src=['"](.*?)['"]/;
                                var src = regex.exec(imagehtml)[1];
                                var tempImage = new Image();
                                tempImage.src = src;
                                var canvas = document.createElement("canvas");
                                canvas.width = tempImage.width;
                                canvas.height = tempImage.height;
                                var ctx = canvas.getContext("2d");
                                ctx.drawImage(tempImage, 0, 0);
                                var imagedata = canvas.toDataURL("image/png");
                                delete doc.content[1].table.body[i][0].text;
                                doc.content[1].table.body[i][0].image = imagedata;
                                doc.content[1].table.body[i][0].fit = [30, 30];
                            }
                        }
                    },
                },
                {
                    extend: 'excel',
                    text: '<i title="export to excel" class="dripicons-document-new"></i>',
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible',
                        format: {
                            body: function(data, row, column, node) {

                                if (column === 0) {
                                    var $cell = $('<div>').html(data);
                                    $cell.find('img').remove();
                                    data = $.trim($cell.text());
                                }

                                return data;
                            }
                        }
                    }
                },
                {
                    extend: 'csv',
                    text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible',
                        format: {
                            body: function(data, row, column, node) {

                                if (column === 0) {
                                    var $cell = $('<div>').html(data);
                                    $cell.find('img').remove();
                                    data = $.trim($cell.text());
                                }

                                return data;
                            }
                        }
                    }
                },
                {
                    extend: 'print',
                    title: '',
                    text: '<i title="print" class="fa fa-print"></i>',
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible',
                        stripHtml: false
                    },
                    repeatingHead: {
                        logo: logoUrl,
                        logoPosition: 'left',
                        logoStyle: '',
                        title: '<h3>Product List</h3>'
                    }
                    /*customize: function ( win ) {
                        $(win.document.body)
                            .prepend(
                                '<img src="http://datatables.net/media/images/logo-fade.png" style="margin:10px;" />'
                            );
                    }*/
                },
            ]);
        @endcan

        @can('products-delete')
            buttons.push([{
                text: '<i title="delete" class="dripicons-cross"></i>',
                className: 'buttons-delete',
                action: function(e, dt, node, config) {
                    if (user_verified == '1') {
                        product_id.length = 0;
                        $(':checkbox:checked').each(function(i) {
                            if (i) {
                                var product_data = $(this).closest('tr').data('product');
                                if (product_data)
                                    product_id[i - 1] = product_data[12];
                            }
                        });
                        if (product_id.length && confirmDelete()) {
                            $.ajax({
                                type: 'POST',
                                url: 'products/deletebyselection',
                                data: {
                                    productIdArray: product_id
                                },
                                success: function(data) {
                                    alert(data);
                                    //dt.rows({ page: 'current', selected: true }).deselect();
                                    dt.rows({
                                        page: 'current',
                                        selected: true
                                    }).remove().draw(false);
                                }
                            });
                        } else if (!product_id.length)
                            alert('No product is selected!');
                    } else
                        alert('This feature is disable for demo!');
                }
            }, ]);
        @endcan

        buttons.push([{
            extend: 'colvis',
            text: '<i title="column visibility" class="fa fa-eye"></i>',
            columns: ':gt(0)'
        }, ]);

        $(document).ready(function() {
            var table = $('#product-data-table').DataTable({
    responsive: true,
    fixedHeader: {
        header: true,
        footer: true
    },
    processing: true,
    serverSide: true,
    ajax: {
        url: "{{ url('products/product-data') }}",
        data: function(d) {
            d.all_permission = all_permission;
            d.product_index_type = product_index_type;
            d.warehouse_id = $('#warehouse_id').val();
            d.product_type = (product_index_type === 'single') ? 'standard' : (product_index_type === 'combo') ? 'combo' : ($('#product_type').val() || 'all');
            d.brand_id = $('#brand_id').val();
            d.category_id = $('#category_id').val();
            d.unit_id = $('#unit_id').val();
            d.tax_id = $('#tax_id').val();
            d.imeiorvariant = $('#imeiorvariant').val();
            d.stock_filter = $('#stock_filter').val();
        },
        type: "post"
    },
    createdRow: function(row, data, dataIndex) {
        $(row).addClass('product-link');
        $(row).attr('data-product', data['product']);
        $(row).attr('data-imagedata', data['imagedata']);
    },
    columns: columns,

    columnDefs: [
        {
            targets: product_index_type === 'all' ? 6 : 5,
            width: "130px",
            className: "text-center"
        },
        {
            orderable: false,
            targets: product_index_type === 'all' ? (role_id <= 2 ? [0, 10] : [0, 8]) : (role_id <= 2 ? [0, 9] : [0, 7])
        },
        {
            render: function(data, type, row, meta) {
                if (type === 'display') {
                    data =
                        '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                }
                return data;
            },
            checkboxes: {
                selectRow: true,
                selectAllRender:
                    '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
            },
            targets: [0]
        }
    ],

    select: {
        style: 'multi',
        selector: 'td:first-child'
    },
    order: [['1', 'asc']],
    lengthMenu: [
        [10, 25, 50, -1],
        [10, 25, 50, "All"]
    ],
    dom: '<"row"lfB>rtip',
    buttons: buttons,
});


            let hasFilters = window.location.search.length > 0;
            if (hasFilters) {
                $('#filter-card').show();
            }

            $('#warehouse_id, #product_type, #brand_id, #category_id, #unit_id, #tax_id, #imeiorvariant, #stock_filter')
                .on('change', function() {
                    table.ajax.reload();
                });

            // Show loader on request
            table.on('preXhr.dt', function() {
                $('#filter-loading').show();
            });

            // Hide loader after draw
            table.on('xhr.dt', function() {
                $('#filter-loading').hide();
            });
        });

        $('select').selectpicker();
    </script>
@endpush
