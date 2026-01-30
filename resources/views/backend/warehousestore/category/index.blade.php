@extends('backend.layout.main') @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        @can('warehouse-stores-add')
            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#ws-category-modal"><i class="dripicons-plus"></i> {{__("db.Add Category")}}</button>&nbsp;
        @endcan
    </div>
    <div class="table-responsive">
        <table id="category-table" class="table" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.category')}}</th>
                    <th>{{__('db.Parent Category')}}</th>
                    <th>{{__('db.Number of Raw Material')}}</th>
                    <th>{{__('db.Stock Quantity')}}</th>
                    <th>{{__('db.Stock Worth') . '(' . __('db.Price') . '/' . __('db.Cost') . ')'}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
        </table>
    </div>
</section>

<div id="ws-category-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
        {!! Form::open(['route' => 'warehouse-stores.category.store', 'method' => 'post', 'files' => true, 'id' => 'ws-category-form']) !!}
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Category')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
      </div>
      <div class="modal-body">
        <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
        <div class="row">
            <div class="col-md-6 form-group">
                <label>{{__('db.name')}} *</label>
                {{Form::text('name',null, array('required' => 'required', 'class' => 'form-control'))}}
                <x-validation-error fieldName="name" />
            </div>
            <div class="col-md-6 form-group">
                <label>{{__('db.Image')}}</label>
                <input type="file" name="image" class="form-control">
                <x-validation-error fieldName="image" />
            </div>
            <div class="col-md-6 form-group">
                <label>{{__('db.Parent Category')}}</label>
                <select name="parent_id" class="form-control selectpicker" id="parent">
                    <option value="">No {{__('db.parent')}}</option>
                    @foreach($categories_list as $category)
                    <option value="{{$category->id}}">{{$category->name}}</option>
                    @endforeach
                </select>
                <x-validation-error fieldName="parent_id" />
            </div>
        </div>
        <div class="form-group">
            <input type="hidden" name="ajax" value="0">
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
          </div>
        </div>
      {!! Form::close() !!}
    </div>
  </div>
</div>

<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
        {{ Form::open(['url' => '', 'method' => 'PUT', 'files' => true, 'id' => 'editCategoryForm']) }}
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title">{{__('db.Update Category')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
      </div>
      <div class="modal-body">
        <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
        <div class="row">
            <div class="col-md-6 form-group">
                <label>{{__('db.name')}} *</label>
                {{Form::text('name',null, array('required' => 'required', 'class' => 'form-control'))}}
                <x-validation-error fieldName="name" />
            </div>
            <input type="hidden" name="category_id">
            <div class="col-md-6 form-group">
                <label>{{__('db.Image')}}</label>
                <input type="file" name="image" class="form-control">
                <x-validation-error fieldName="image" />
            </div>
            <div class="col-md-6 form-group">
                <label>{{__('db.Parent Category')}}</label>
                <select name="parent_id" class="form-control selectpicker" id="parent">
                    <option value="">No {{__('db.parent')}}</option>
                    @foreach($categories_list as $category)
                    <option value="{{$category->id}}">{{$category->name}}</option>
                    @endforeach
                </select>
                <x-validation-error fieldName="parent_id" />
            </div>
        </div>
        <div class="form-group">
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
          </div>
        </div>
      {{ Form::close() }}
    </div>
  </div>
</div>

@endsection
@push('scripts')
<script type="text/javascript">
    $("ul#basement").siblings('a').attr('aria-expanded','true');
    $("ul#basement").addClass("show");
    $("ul#basement #warehouse-store-category-menu").addClass("active");

    function confirmDelete() {
      if (confirm("If you delete category all warehouse store items under this category will also be deleted. Are you sure want to delete?")) {
          return true;
      }
      return false;
    }

    var category_id = [];
    var user_verified = <?php echo json_encode(env('USER_VERIFIED')) ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    $(document).on("click", ".open-EditCategoryDialog", function(){
        $("#editCategoryForm")[0].reset();
        $("#editModal input[name='category_id']").val('');
        
        var id = $(this).data('id').toString();
        var url = "{{ url('warehouse-stores/category') }}/" + id + "/edit";
        $.get(url, function(data){
            if(data.error) {
                alert(data.error);
                return;
            }
            $("#editModal input[name='name']").val(data.name || data['name'] || '');
            $("#editModal input[name='category_id']").val(data.id || data['id'] || '');
            
            var parentId = data.parent_id || data['parent_id'] || '';
            $("#editModal select[name='parent_id']").val(parentId);
            $("#editModal select[name='parent_id']").selectpicker('refresh');

            var updateUrl = "{{ url('warehouse-stores/category') }}/" + (data.id || data['id']);
            $("#editCategoryForm").attr('action', updateUrl);
        }).fail(function(xhr) {
            alert('Error loading category data');
        });
    });

    $('#category-table').DataTable( {
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"{{ route('warehouse-stores.category.data') }}",
            dataType: "json",
            type:"post"
        },
        "createdRow": function( row, data, dataIndex ) {
            $(row).attr('data-id', data['id']);
        },
        "columns": [
            {"data": "key"},
            {"data": "name"},
            {"data": "parent_id"},
            {"data": "number_of_product"},
            {"data": "stock_qty"},
            {"data": "stock_worth"},
            {"data": "options"},
        ],
        'language': {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
             "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{__("db.Search")}}',
            'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
            }
        },
        order:[['2', 'asc']],
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 1, 3, 4, 5, 6]
            },
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }

                   return data;
                },
                'checkboxes': {
                   'selectRow': true,
                   'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                'targets': [0]
            }
        ],
        'select': { style: 'multi',  selector: 'td:first-child'},
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],

        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="dripicons-document-new"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                text: '<i title="delete" class="dripicons-cross"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(user_verified == '1') {
                        category_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                category_id[i-1] = $(this).closest('tr').data('id');
                            }
                        });
                        if(category_id.length && confirm("If you delete category all warehouse store items under this category will also be deleted. Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'{{ url("warehouse-stores/category/deletebyselection") }}',
                                data:{
                                    categoryIdArray: category_id
                                },
                                success:function(data){
                                    dt.rows({ page: 'current', selected: true }).deselect();
                                    dt.rows({ page: 'current', selected: true }).remove().draw(false);
                                }
                            });
                        }
                        else if(!category_id.length)
                            alert('No category is selected!');
                    }
                    else
                        alert('This feature is disable for demo!');
                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="fa fa-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    } );

</script>
@endpush
