@extends('backend.layout.main')

@push('css')
<style>
.switch { position: relative; display: inline-block; width: 36px; height: 20px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .3s; border-radius: 20px; }
.slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.3); }
input:checked + .slider { background-color: #28a745; }
input:checked + .slider:before { transform: translateX(16px); }
.slider.round { border-radius: 20px; }
.slider.round:before { border-radius: 50%; }
#menu-subcategories-sortable { list-style: none; padding: 0; margin: 0; }
#menu-subcategories-sortable .list-group-item { cursor: move; display: flex; align-items: center; padding: 0.65rem 0.85rem; }
#menu-subcategories-sortable .list-group-item .drag-handle { color: #6c757d; margin-right: 0.5rem; }
#menu-subcategories-sortable .list-group-item.ui-sortable-helper { box-shadow: 0 4px 12px rgba(0,0,0,.15); }
</style>
@endpush

@section('content')
<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        @can('categories-index')
            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#addSubcategoryModal"><i class="dripicons-plus"></i> {{ __('Add Product Subcategory') }}</button>
            <button type="button" class="btn btn-outline-secondary" id="btn-arrange-subcategory-menu" data-toggle="modal" data-target="#arrangeSubcategoryModal"><i class="dripicons-move"></i> {{ __('Arrange navbar menu') }}</button>
        @endcan
    </div>
    <div class="table-responsive mt-3">
        <table id="subcategory-table" class="table" style="width: 100%">
            <thead>
                <tr>
                    <th>{{ __('Name (English)') }}</th>
                    <th>{{ __('Name (Arabic)') }}</th>
                    <th>{{ __('db.category') }}</th>
                    <th>{{ __('Slug') }}</th>
                    <th class="not-exported">{{ __('Show in navbar') }}</th>
                    <th class="not-exported">{{ __('db.action') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</section>

{{-- Add Subcategory Modal --}}
<div class="modal fade" id="addSubcategoryModal" tabindex="-1" role="dialog" aria-labelledby="addSubcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fcolor" id="addSubcategoryModalLabel">Add Product Subcategory</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="subcategoryAjaxForm" class="modal-form" action="{{ route('subcategory.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <div class="col-12 mb-2">
                                    <label for="subcate_banner_img"><span class="fcolor">Subcategory Banner Image</span></label>
                                </div>
                                <div class="col-md-12 showBannerImage mb-3">
                                    <img src="{{ url('images/zummXD2dvAtI.png') }}" alt="Banner" class="img-thumbnail" style="max-height: 120px;">
                                </div>
                                <input type="file" name="subcate_banner_img" id="subcate_banner_img" class="form-control bannerimage">
                                <p id="errsubcate_banner_img" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <div class="col-12 mb-2">
                                    <label for="image"><span class="fcolor">Image</span></label>
                                </div>
                                <div class="col-md-12 showImage mb-3">
                                    <img src="{{ url('images/zummXD2dvAtI.png') }}" alt="Image" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                                <input type="file" name="image" id="image" class="form-control image">
                                <p id="errimage" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="category_id"><span class="fcolor">Categories</span></label>
                        <select class="form-control" name="category_id" id="category_id">
                            <option value="">Select a Category</option>
                            @foreach($categories_list as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <p id="errcategory_id" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="name_english"><span class="fcolor">Name (English)</span></label>
                        <input type="text" class="form-control" name="name_english" id="name_english" value="" placeholder="Enter name (english)">
                        <p id="errname_english" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="name_arabic"><span class="fcolor">Name (Arabic)</span></label>
                        <input type="text" class="form-control" name="name_arabic" id="name_arabic" value="" placeholder="Enter name (arabic)">
                        <p id="errname_arabic" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="slug"><span class="fcolor">Slug</span></label>
                        <input type="text" class="form-control" name="slug" id="slug" value="" placeholder="Enter slug">
                        <p id="errslug" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="description_english"><span class="fcolor">Subcategory Description (English)</span></label>
                        <textarea class="form-control" name="description_english" id="description_english" placeholder="Enter Subcategory Description (English)"></textarea>
                        <p id="errdescription_english" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="description_arabic"><span class="fcolor">Subcategory Description (Arabic)</span></label>
                        <textarea class="form-control" name="description_arabic" id="description_arabic" placeholder="Enter Subcategory Description (Arabic)"></textarea>
                        <p id="errdescription_arabic" class="mb-0 text-danger em"></p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button id="subcategorySubmitBtn" type="button" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Subcategory Modal --}}
<div id="editSubcategoryModal" tabindex="-1" role="dialog" aria-labelledby="editSubcategoryModalLabel" aria-hidden="true" class="modal fade text-left">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            {{ Form::open(['url' => '', 'method' => 'PUT', 'files' => true, 'id' => 'editSubcategoryForm']) }}
            <div class="modal-header">
                <h5 class="modal-title" id="editSubcategoryModalLabel">Update Product Subcategory</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label>Subcategory Banner Image</label>
                            <div class="showBannerImageEdit mb-2"></div>
                            <input type="file" name="subcate_banner_img" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label>Image</label>
                            <div class="showImageEdit mb-2"></div>
                            <input type="file" name="image" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Categories *</label>
                    <select name="category_id" class="form-control" id="edit_category_id">
                        @foreach($categories_list as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Name (English) *</label>
                    {{ Form::text('name_english', null, ['required' => 'required', 'class' => 'form-control']) }}
                </div>
                <div class="form-group">
                    <label>Name (Arabic)</label>
                    {{ Form::text('name_arabic', null, ['class' => 'form-control']) }}
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    {{ Form::text('slug', null, ['class' => 'form-control']) }}
                </div>
                <div class="form-group">
                    <label>Subcategory Description (English)</label>
                    {{ Form::textarea('description_english', null, ['class' => 'form-control', 'rows' => 3]) }}
                </div>
                <div class="form-group">
                    <label>Subcategory Description (Arabic)</label>
                    {{ Form::textarea('description_arabic', null, ['class' => 'form-control', 'rows' => 3]) }}
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">{{ __('db.submit') }}</button>
                </div>
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>

{{-- Arrange Subcategory Navbar Modal (select category then drag-drop subcategories) --}}
<div id="arrangeSubcategoryModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="arrangeSubcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-sm border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="arrangeSubcategoryModalLabel"><i class="dripicons-view-list text-primary mr-2"></i>{{ __('Arrange navbar menu') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-muted small mb-3">{{ __('Select a category, then drag items to reorder subcategories in the website navbar.') }}</p>
                <div class="form-group">
                    <label>{{ __('db.category') }}</label>
                    <select class="form-control" id="arrange-subcategory-category-id" name="category_id">
                        <option value="">{{ __('Select a Category') }}</option>
                        @foreach($categories_list as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="menu-subcategories-loading" class="text-center py-4 text-muted d-none"><i class="dripicons-loading dripicons-spin"></i> {{ __('Loading...') }}</div>
                <ul id="menu-subcategories-sortable" class="list-group list-group-flush d-none"></ul>
                <div id="menu-subcategories-empty" class="alert alert-light border text-muted text-center d-none">{{ __('No subcategories are set to show in navbar for this category. Enable "Show in navbar" for subcategories first.') }}</div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="menu-subcategories-save"><i class="dripicons-checkmark mr-1"></i>{{ __('Save order') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $("ul#product").siblings('a').attr('aria-expanded', 'true');
    $("ul#product").addClass("show");
    $("ul#product #subcategory-menu").addClass("active");

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    var noImageUrl = "{{ url('images/zummXD2dvAtI.png') }}";

    // Banner image preview (add modal)
    $(document).on('change', '#subcate_banner_img', function() {
        var input = this;
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#addSubcategoryModal .showBannerImage img').attr('src', e.target.result);
            };
            reader.readAsDataURL(input.files[0]);
        }
    });
    // Image preview (add modal)
    $(document).on('change', '#addSubcategoryModal #image', function() {
        var input = this;
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#addSubcategoryModal .showImage img').attr('src', e.target.result);
            };
            reader.readAsDataURL(input.files[0]);
        }
    });

    // Add subcategory - AJAX submit
    $('#subcategorySubmitBtn').on('click', function() {
        var form = $('#subcategoryAjaxForm');
        var btn = $(this);
        $('.em').text('');
        btn.prop('disabled', true);
        var formData = new FormData(form[0]);
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#addSubcategoryModal').modal('hide');
                form[0].reset();
                $('#addSubcategoryModal .showBannerImage img').attr('src', noImageUrl);
                $('#addSubcategoryModal .showImage img').attr('src', noImageUrl);
                $('#subcategory-table').DataTable().ajax.reload(null, false);
                if (res.message) alert(res.message);
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(field, messages) {
                        var el = $('#err' + field);
                        if (el.length) el.text(Array.isArray(messages) ? messages[0] : messages);
                    });
                } else {
                    alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Something went wrong.');
                }
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Toggle Show in navbar (same as category)
    $(document).on('change', '.toggle-show-in-menu-sub', function() {
        var chk = $(this);
        var id = chk.data('id');
        var showInMenu = chk.prop('checked') ? 1 : 0;
        chk.prop('disabled', true);
        $.post('{{ route("subcategory.toggle-show-in-menu") }}', { _token: '{{ csrf_token() }}', subcategory_id: id, show_in_menu: showInMenu })
            .done(function(r) {
                if (r.success) $('#subcategory-table').DataTable().ajax.reload(null, false);
            })
            .fail(function() { chk.prop('checked', showInMenu !== 1); })
            .always(function() { chk.prop('disabled', false); });
    });

    // Edit - load data
    $(document).on('click', '.open-EditSubcategoryDialog', function() {
        var id = $(this).data('id');
        var url = "{{ url('subcategory') }}/" + id + "/edit";
        $.get(url, function(data) {
            $('#editSubcategoryForm').attr('action', "{{ url('subcategory') }}/" + data.id);
            $('#editSubcategoryForm select[name="category_id"]').val(data.category_id);
            $('#editSubcategoryForm input[name="name_english"]').val(data.name_english);
            $('#editSubcategoryForm input[name="name_arabic"]').val(data.name_arabic);
            $('#editSubcategoryForm input[name="slug"]').val(data.slug);
            $('#editSubcategoryForm textarea[name="description_english"]').val(data.description_english);
            $('#editSubcategoryForm textarea[name="description_arabic"]').val(data.description_arabic);
            var bannerUrl = data.subcate_banner_img ? "{{ url('images/subcategory/banner') }}/" + data.subcate_banner_img : noImageUrl;
            var imgUrl = data.image ? "{{ url('images/subcategory') }}/" + data.image : noImageUrl;
            $('#editSubcategoryModal .showBannerImageEdit').html('<img src="' + bannerUrl + '" alt="Banner" class="img-thumbnail" style="max-height: 100px;">');
            $('#editSubcategoryModal .showImageEdit').html('<img src="' + imgUrl + '" alt="Image" class="img-thumbnail" style="max-height: 80px;">');
        });
    });

    // Arrange subcategory modal: load subcategories when category is selected
    var menuSubcategoriesSortable = null;
    $('#arrange-subcategory-category-id').on('change', function() {
        var categoryId = $(this).val();
        $('#menu-subcategories-sortable').addClass('d-none').empty();
        $('#menu-subcategories-empty').addClass('d-none');
        if (!categoryId) {
            return;
        }
        $('#menu-subcategories-loading').removeClass('d-none');
        $.get('{{ route("subcategory.menu-subcategories") }}', { category_id: categoryId })
            .done(function(r) {
                $('#menu-subcategories-loading').addClass('d-none');
                if (r.subcategories && r.subcategories.length) {
                    r.subcategories.forEach(function(s) {
                        $('#menu-subcategories-sortable').append(
                            '<li class="list-group-item" data-id="' + s.id + '"><i class="dripicons-move drag-handle"></i><span>' + (s.name_english || '') + '</span></li>'
                        );
                    });
                    $('#menu-subcategories-sortable').removeClass('d-none');
                    if (menuSubcategoriesSortable) { $('#menu-subcategories-sortable').sortable('destroy'); }
                    $('#menu-subcategories-sortable').sortable({ handle: '.drag-handle', placeholder: 'list-group-item list-group-item-secondary', forcePlaceholderSize: true });
                } else {
                    $('#menu-subcategories-empty').removeClass('d-none');
                }
            })
            .fail(function() {
                $('#menu-subcategories-loading').addClass('d-none');
                $('#menu-subcategories-empty').removeClass('d-none').text('{{ __("Failed to load subcategories.") }}');
            });
    });

    // Every time arrange modal opens: fresh state, no category pre-selected
    $('#arrangeSubcategoryModal').on('show.bs.modal', function() {
        var $cat = $('#arrange-subcategory-category-id');
        $cat.val('');
        $cat.prop('selectedIndex', 0);
        $('#menu-subcategories-sortable').addClass('d-none').empty();
        $('#menu-subcategories-empty').addClass('d-none');
        $('#menu-subcategories-loading').addClass('d-none');
        if (menuSubcategoriesSortable) {
            $('#menu-subcategories-sortable').sortable('destroy');
            menuSubcategoriesSortable = null;
        }
    });

    $('#menu-subcategories-save').on('click', function() {
        var ids = [];
        $('#menu-subcategories-sortable .list-group-item').each(function() { ids.push(parseInt($(this).data('id'), 10)); });
        if (!ids.length) return;
        var btn = $(this).prop('disabled', true);
        $.post('{{ route("subcategory.save-menu-order") }}', { _token: '{{ csrf_token() }}', order: ids })
            .done(function(r) {
                if (r.success) { $('#arrangeSubcategoryModal').modal('hide'); $('#subcategory-table').DataTable().ajax.reload(null, false); }
            })
            .always(function() { btn.prop('disabled', false); });
    });

    $('#subcategory-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('subcategory.data') }}",
            dataType: "json",
            type: "post"
        },
        columns: [
            { data: "name_english" },
            { data: "name_arabic" },
            { data: "category_name" },
            { data: "slug" },
            { data: "show_in_menu" },
            { data: "options", orderable: false, searchable: false }
        ],
        order: [[ 4, 'asc' ]],
        language: {
            lengthMenu: '_MENU_ {{ __("db.records per page") }}',
            info: '<small>{{ __("db.Showing") }} _START_ - _END_ (_TOTAL_)</small>',
            search: '{{ __("db.Search") }}',
            paginate: {
                previous: '<i class="dripicons-chevron-left"></i>',
                next: '<i class="dripicons-chevron-right"></i>'
            }
        },
        columnDefs: [
            { orderable: false, targets: [0, 1, 2, 3, 4, 5] }
        ],
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons: [
            { extend: 'pdf', text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>', exportOptions: { columns: ':visible:Not(.not-exported)' } },
            { extend: 'excel', text: '<i title="export to excel" class="dripicons-document-new"></i>', exportOptions: { columns: ':visible:Not(.not-exported)' } },
            { extend: 'csv', text: '<i title="export to csv" class="fa fa-file-text-o"></i>', exportOptions: { columns: ':visible:Not(.not-exported)' } },
            { extend: 'print', text: '<i title="print" class="fa fa-print"></i>', exportOptions: { columns: ':visible:Not(.not-exported)' } }
        ]
    });
</script>
@endpush
