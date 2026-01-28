@extends('backend.layout.main') @section('content')

<x-validation-error fieldName="unit_code" />
<x-validation-error fieldName="unit_name" />
<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <a href="#" data-toggle="modal" data-target="#createUnitModal" class="btn btn-info"><i class="dripicons-plus"></i> {{__('db.Add Unit')}}</a>&nbsp;
    </div>
    <div class="table-responsive">
        <table id="unit-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.Code')}}</th>
                    <th>{{__('db.name')}}</th>
                    <th>{{__('db.Base Unit')}}</th>
                    <th>{{__('db.Operator')}}</th>
                    <th>{{__('db.Operation Value')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_unit_list as $key=>$unit)
                <tr data-id="{{$unit->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $unit->unit_code }}</td>
                    <td>{{ $unit->unit_name }}</td>
                    @if($unit->base_unit)
                        <?php $base_unit = DB::table('units')->where('id', $unit->base_unit)->first(); ?>
                        <td>{{ $base_unit->unit_name }}</td>
                    @else
                        <td>N/A</td>
                    @endif
                    @if($unit->operator)
                        <td>{{ $unit->operator }}</td>
                    @else
                        <td>N/A</td>
                    @endif
                    @if($unit->operation_value)
                        <td>{{ $unit->operation_value }}</td>
                    @else
                        <td>N/A</td>
                    @endif
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" data-id="{{$unit->id}}" class="open-EditUnitDialog btn btn-link"><i class="dripicons-document-edit"></i> {{__('db.edit')}}
                                </button>
                                </li>
                                <li class="divider"></li>
                                {{ Form::open(['route' => ['rawmaterials.unit.destroy', $unit->id], 'method' => 'DELETE'] ) }}
                                <li>
                                    <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> {{__('db.delete')}}</button>
                                </li>
                                {{ Form::close() }}
                            </ul>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<!-- Create Unit Modal -->
<div id="createUnitModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
      <div class="modal-content">
          {!! Form::open(['route' => 'rawmaterials.unit.store', 'method' => 'post']) !!}
          <div class="modal-header">
              <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Unit')}}</h5>
              <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
          </div>
          <div class="modal-body">
              <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
              <div class="form-group">
                  <label>{{__('db.Code')}} *</label>
                  {{Form::text('unit_code',null,array('required' => 'required', 'class' => 'form-control'))}}
              </div>
              <div class="form-group">
                  <label>{{__('db.name')}} *</label>
                  {{Form::text('unit_name',null,array('required' => 'required', 'class' => 'form-control'))}}
              </div>
              <div class="form-group">
                  <label>{{__('db.Base Unit')}}</label>
                  <select class="form-control selectpicker" id="base_unit_create" name="base_unit">
                      <option value="">No Base Unit</option>
                      @foreach($lims_unit_list as $unit)
                          @if($unit->base_unit==null)
                          <option value="{{$unit->id}}">{{$unit->unit_name}}</option>
                          @endif
                      @endforeach
                  </select>
              </div>
              <div class="form-group operator">
                  <label>{{ __('db.Operator') }}</label>
                  <select name="operator" class="form-control">
                      <option value="">{{ __('Select an operator') }}</option>
                      <option value="*" {{ old('operator') == '*' ? 'selected' : '' }}>*</option>
                      <option value="/" {{ old('operator') == '/' ? 'selected' : '' }}>/</option>
                  </select>
              </div>
              <div class="form-group operation_value">
                  <label>{{__('db.Operation Value')}}</label><input type="number" name="operation_value" placeholder="{{ __('db.Enter operation value') }}" class="form-control" step="any"/>
              </div>
              <div class="form-text text-muted mt-2 mb-4">
                  <strong>Example conversions:</strong><br>
                  1 Dozen = 1<strong>*</strong>12 Piece<br>
                  1 Gram = 1<strong>/</strong>1000 KG
              </div>

              <input type="submit" id="create_unit" value="{{__('db.submit')}}" class="btn btn-primary">
          </div>
      {{ Form::close() }}
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        {!! Form::open(['url' => '', 'method' => 'PUT', 'id' => 'editUnitForm']) !!}
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title"> {{__('db.Update Unit')}}</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
        </div>
        <div class="modal-body">
          <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
          <input type="hidden" name="unit_id">
          <div class="form-group">
            <label>{{__('db.Code')}} *</label>
            {{Form::text('unit_code',null,array('required' => 'required', 'class' => 'form-control'))}}
          </div>
          <div class="form-group">
              <label>{{__('db.name')}} *</label>
              {{Form::text('unit_name',null,array('required' => 'required', 'class' => 'form-control'))}}
          </div>
          <div class="form-group">
              <label>{{__('db.Base Unit')}}</label>
              <select class="form-control selectpicker" id="base_unit_edit" name="base_unit">
                  <option value="">No Base Unit</option>
                  @foreach($lims_unit_list as $unit)
                      @if($unit->base_unit==null)
                      <option value="{{$unit->id}}">{{$unit->unit_name}}</option>
                      @endif
                  @endforeach
              </select>
          </div>
          <div class="form-group operator">
              <label>{{ __('db.Operator') }}</label>
              <select name="operator" class="form-control selectpicker">
                  <option value="">{{ __('Select an operator') }}</option>
                  <option value="*">*</option>
                  <option value="/">/</option>
              </select>
          </div>
          <div class="form-group operation_value">
              <label>{{__('db.Operation Value')}}</label><input type="number" name="operation_value" placeholder="{{ __('db.Enter operation value') }}" class="form-control" step="any"/>
          </div>
          <div class="form-text text-muted mt-2 mb-4">
              <strong>Example conversions:</strong><br>
              1 Dozen = 1<strong>*</strong>12 Piece<br>
              1 Gram = 1<strong>/</strong>1000 KG
          </div>

          <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
        </div>
        {{ Form::close() }}
      </div>
    </div>
</div>

@endsection
@push('scripts')
<script type="text/javascript">
    $("ul#rawmaterial").siblings('a').attr('aria-expanded','true');
    $("ul#rawmaterial").addClass("show");
    $("ul#rawmaterial #rawmaterial-unit-menu").addClass("active");

    var unit_id = [];
    var user_verified = <?php echo json_encode(env('USER_VERIFIED')) ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(".operator").hide();
    $(".operation_value").hide();
     function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }
    $(document).ready(function() {
    $(document).on('click', '.open-EditUnitDialog', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        
        // Clean up any existing modals/backdrops
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        $('body').css('padding-right', '');
        $('.modal').removeClass('show');
        $('.modal').css('display', 'none');
        
        
        // Reset form
        $("#editUnitForm")[0].reset();
        $("#editModal input[name='unit_id']").val('');
        $("#editModal .operator").hide();
        $("#editModal .operation_value").hide();
        
        var id = $(this).data('id').toString();
        var url = "{{ url('rawmaterials/unit') }}/" + id + "/edit";
        

        $.get(url, function(data) {
            
            if(data.error) {
                console.error('Error in response:', data.error);
                alert(data.error);
                return;
            }
            
            // Populate form fields
            $("#editModal input[name='unit_code']").val(data.unit_code || data['unit_code'] || '');
            $("#editModal input[name='unit_name']").val(data.unit_name || data['unit_name'] || '');
            $("#editModal input[name='unit_id']").val(data.id || data['id'] || '');
            
            
            var operator = data.operator || data['operator'] || '';
            var operationValue = data.operation_value || data['operation_value'] || '';
            var baseUnit = data.base_unit || data['base_unit'] || '';
            
            
            $("#editModal select[name='operator']").val(operator);
            $("#editModal input[name='operation_value']").val(operationValue);
            
            // Set base unit and refresh selectpicker
            $("#base_unit_edit").val(baseUnit);
            $("#base_unit_edit").selectpicker('refresh');
            $("#editModal select[name='operator']").selectpicker('refresh');

            if (baseUnit != null && baseUnit != '' && baseUnit != 0) {
                $("#editModal .operator").show();
                $("#editModal .operation_value").show();
            } else {
                $("#editModal .operator").hide();
                $("#editModal .operation_value").hide();
            }

            var updateUrl = "{{ url('rawmaterials/unit') }}/" + (data.id || data['id']);
            $("#editUnitForm").attr('action', updateUrl);
            
            
            // Use Bootstrap's modal API - this handles everything properly
            $('#editModal').appendTo('body').modal('show');
            
        }).fail(function(xhr) {
            console.error('AJAX failed:', xhr);
            alert('Error loading unit data');
            // Remove backdrop on error
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            $('body').css('padding-right', '');
        });

    });

    $( "#select_all" ).on( "change", function() {
        if ($(this).is(':checked')) {
            $("tbody input[type='checkbox']").prop('checked', true);
        }
        else {
            $("tbody input[type='checkbox']").prop('checked', false);
        }
    });

    $('.open-CreateUnitDialog').on('click', function() {
        $(".operator").hide();
        $(".operation_value").hide();

    });

    $('#base_unit_create').on('change', function() {
        if($(this).val()){
            $("#createUnitModal .operator").attr('required',true).show();
            $("#createUnitModal .operation_value").show();
        }
        else{
            $("#createUnitModal .operator").hide();
            $("#createUnitModal .operation_value").hide();
        }
    });

    // Handle modal close to clean up backdrop
    $('#editModal').on('hidden.bs.modal', function () {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        $('body').css('padding-right', '');
    });
    
    $('#base_unit_edit').on('change', function() {
        if($(this).val()){
            $("#editModal .operator").show();
            $("#editModal .operation_value").show();
        }
        else{
            $("#editModal .operator").hide();
            $("#editModal .operation_value").hide();
        }
    });

    $(document).on('click', '#create_unit', function(e) {
        e.preventDefault();
        let form = $(this).closest('form').closest('.modal-content').find('form');
        let formData = form.serialize();

        $.ajax({
            url: form.attr('action') || '{{ route("rawmaterials.unit.store") }}',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    $('#createUnitModal').modal('hide');
                    form.trigger('reset');
                    location.reload();
                } else {
                    alert('Failed to create unit');
                }
            },
            error: function(xhr) {
                if(xhr.responseJSON && xhr.responseJSON.errors) {
                    alert('Validation errors: ' + JSON.stringify(xhr.responseJSON.errors));
                } else {
                    alert('Failed to create unit');
                }
            }
        });
    });
});

    $('#unit-table').DataTable( {
        "order": [],
        'language': {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
             "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{__("db.Search")}}',
            'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
            }
        },
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 6]
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
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="dripicons-document-new"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                text: '<i title="delete" class="dripicons-cross"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(user_verified == '1') {
                        unit_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                unit_id[i-1] = $(this).closest('tr').data('id');
                            }
                        });
                        if(unit_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'{{ url("rawmaterials/unit/deletebyselection") }}',
                                data:{
                                    unitIdArray: unit_id
                                },
                                success:function(data){
                                    alert(data);
                                }
                            });
                            dt.rows({ page: 'current', selected: true }).remove().draw(false);
                        }
                        else if(!unit_id.length)
                            alert('No unit is selected!');
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
