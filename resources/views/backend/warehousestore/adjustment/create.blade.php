@extends('backend.layout.main')
@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Add Adjustment')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'warehouse-store-adjustment.store', 'method' => 'post', 'files' => true, 'id' => 'adjustment-form']) !!}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Warehouse')}} *</label>
                                            <select required id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Attach Document')}}</label>
                                            <input type="file" name="document" class="form-control" >
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <label>{{__('db.Select Raw Material')}}</label>
                                        <div class="search-box input-group">
                                            <button type="button" class="btn btn-secondary btn-lg"><i class="fa fa-barcode"></i></button>
                                            <input type="text" name="raw_material_code_name" id="lims_basementcodeSearch" placeholder="{{ __('db.Please type raw material code and select') }}" class="form-control" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-5">
                                    <div class="col-md-12">
                                        <h5>{{__('db.Order Table')}} *</h5>
                                        <div class="table-responsive mt-3">
                                            <table id="myTable" class="table table-hover order-list">
                                                <thead>
                                                    <tr>
                                                        <th>{{__('db.name')}}</th>
                                                        <th>{{__('db.Code')}}</th>
                                                        <th>{{__('db.Unit Cost')}}</th>
                                                        <th>{{__('db.Quantity')}}</th>
                                                        <th>{{__('db.action')}}</th>
                                                        <th><i class="dripicons-trash"></i></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                                <tfoot class="tfoot active">
                                                    <th colspan="3">{{__('db.Total')}}</th>
                                                    <th id="total-qty" colspan="2">0</th>
                                                    <th><i class="dripicons-trash"></i></th>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_qty" />
                                            <input type="hidden" name="item" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{__('db.Note')}}</label>
                                            <textarea rows="5" class="form-control" name="note"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary" id="submit-button">
                                </div>
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
@push('scripts')
<script type="text/javascript">
	$("ul#basement").siblings('a').attr('aria-expanded','true');
    $("ul#basement").addClass("show");
    $("ul#basement #warehouse-store-adjustment-create-menu").addClass("active");
    var lims_basement_array = [];
    var basement_code = [];
    var basement_name = [];
    var basement_qty = [];
    var unit_cost = [];

	$('.selectpicker').selectpicker({
	    style: 'btn-link',
	});

	$(document).ready(function() {
	    var initialWarehouseId = $('select[name="warehouse_id"]').val();
	    if(initialWarehouseId) {
	        loadBasements(initialWarehouseId);
	    }
	});

	function loadBasements(id) {
	    $.get('{{ url("warehouse-store-adjustment/getbasement") }}/' + id, function(data) {
	        lims_basement_array = [];
	        if(data && data.length >= 4) {
	            basement_code = data[0] || [];
	            basement_name = data[1] || [];
	            basement_qty = data[2] || [];
	            unit_cost = data[3] || [];
	            $.each(basement_code, function(index) {
	                if(basement_code[index] && basement_name[index]) {
	                    lims_basement_array.push(basement_code[index] + ' (' + basement_name[index] + ')' + '|' + (unit_cost[index] || 0));
	                }
	            });
	        } else {
	            alert('No items found for this warehouse');
	        }
	    }).fail(function(xhr, status, error) {
	        alert('Error loading items. Please try again.');
	    });
	}

	$('select[name="warehouse_id"]').on('change', function() {
	    var id = $(this).val();
	    if(id) {
	        loadBasements(id);
	    }
	});

	var lims_basementcodeSearch = $('#lims_basementcodeSearch');

	lims_basementcodeSearch.autocomplete({
	    source: function(request, response) {
	        var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
	        response($.grep(lims_basement_array, function(item) {
	            return matcher.test(item);
	        }));
	    },
	    response: function(event, ui) {
	        if (ui.content.length == 1) {
	            var data = ui.content[0].value;
	            $(this).autocomplete( "close" );
	            basementSearch(data);
	        };
	    },
	    select: function(event, ui) {
	        var data = ui.item.value;
	        basementSearch(data);
	    }
	});

	$("#myTable").on('input', '.qty', function() {
	    rowindex = $(this).closest('tr').index();
	    checkQuantity($(this).val(), true);
	});

	$("table.order-list tbody").on("click", ".ibtnDel", function(event) {
	    rowindex = $(this).closest('tr').index();
	    $(this).closest("tr").remove();
	    calculateTotal();
	});

	$(window).keydown(function(e){
	    if (e.which == 13) {
	        var $targ = $(e.target);
	        if (!$targ.is("textarea") && !$targ.is(":button,:submit")) {
	            var focusNext = false;
	            $(this).find(":input:visible:not([disabled],[readonly]), a").each(function(){
	                if (this === e.target) {
	                    focusNext = true;
	                }
	                else if (focusNext){
	                    $(this).focus();
	                    return false;
	                }
	            });
	            return false;
	        }
	    }
	});

	$('#adjustment-form').on('submit',function(e){
	    var rownumber = $('table.order-list tbody tr:last').index();
	    if (rownumber < 0) {
	        alert("Please insert raw material to order table!")
	        e.preventDefault();
	    }
	});

	function basementSearch(data){
		$.ajax({
            type: 'GET',
            url: '{{ url("warehouse-store-adjustment/lims_basement_search") }}',
            data: {
                data: data
            },
            success: function(data) {
                if(!data || data.length == 0) {
                    alert('Item not found!');
                    return;
                }
                var flag = 1;
                $(".basement-code").each(function(i) {
                    if ($(this).val() == data[1]) {
                        rowindex = i;
	                    var qty = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val()) + 1;
	                    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
	                    checkQuantity(qty);
	                    flag = 0;
                    }
                });
                $("input[name='raw_material_code_name']").val('');
                if(flag){
                    var newRow = $("<tr>");
                    var cols = '';
                    cols += '<td>' + (data[0] || '') + '</td>';
                    cols += '<td>' + (data[1] || '') + '</td>';
                    var cost = data[4] || 0;
                    cols += '<td>' + cost + '<input type="hidden" name="unit_cost[]" value="'+cost+'" /></td>';
                    cols += '<td><input type="number" class="form-control qty" name="qty[]" value="1" required step="any" /></td>';
                    cols += '<td class="action"><select name="action[]" class="form-control act-val"><option value="-">{{__("db.Subtraction")}}</option><option value="+">{{__("db.Addition")}}</option></select></td>';
                    cols += '<td><button type="button" class="ibtnDel btn btn-md btn-danger">{{__("db.delete")}}</button></td>';
                    cols += '<input type="hidden" class="basement-code" name="product_code[]" value="' + (data[1] || '') + '"/>';
                    cols += '<input type="hidden" class="basement-id" name="product_id[]" value="' + (data[2] || '') + '"/>';

                    newRow.append(cols);
                    $("table.order-list tbody").append(newRow);
                    rowindex = newRow.index();
                    calculateTotal();
                }
            },
            error: function(xhr, status, error) {
                alert('Error searching for item.');
            }
        });
	}

	function checkQuantity(qty) {
	    var row_basement_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
	    var action = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.act-val').val();
	    var pos = basement_code.indexOf(row_basement_code);

	    if (pos >= 0 && (qty > parseFloat(basement_qty[pos])) && (action == '-') ) {
	        alert('Quantity exceeds stock quantity!');
            var row_qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val();
            row_qty = row_qty.substring(0, row_qty.length - 1);
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(row_qty);
	    }
	    else {
	        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(qty);
	    }
	    calculateTotal();
	}

	function calculateTotal() {
	    var total_qty = 0;
	    $(".qty").each(function() {

	        if ($(this).val() == '') {
	            total_qty += 0;
	        } else {
	            total_qty += parseFloat($(this).val());
	        }
	    });
	    $("#total-qty").text(total_qty);
	    $('input[name="total_qty"]').val(total_qty);
	    $('input[name="item"]').val($('table.order-list tbody tr:last').index() + 1);
	}
</script>
@endpush
