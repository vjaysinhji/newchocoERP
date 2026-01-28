@extends('backend.layout.main')
@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Update Adjustment')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => ['rawmaterial-adjustment.update', $lims_adjustment_data->id], 'method' => 'put', 'files' => true, 'id' => 'adjustment-form']) !!}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.reference')}}</label>
                                            <p><strong>{{$lims_adjustment_data->reference_no}}</strong></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Warehouse')}} *</label>
                                            <select required id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <option value="{{$warehouse->id}}" {{$warehouse->id == $lims_adjustment_data->warehouse_id ? 'selected' : ''}}>{{$warehouse->name}}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="warehouse_id_hidden" value="{{$lims_adjustment_data->warehouse_id}}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Attach Document')}}</label>
                                            <input type="file" name="document" class="form-control" >
                                            @if($lims_adjustment_data->document)
                                                <small>Current: {{$lims_adjustment_data->document}}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <label>{{__('db.Select Raw Material')}}</label>
                                        <div class="search-box input-group">
                                            <button type="button" class="btn btn-secondary btn-lg"><i class="fa fa-barcode"></i></button>
                                            <input type="text" name="raw_material_code_name" id="lims_rawmaterialcodeSearch" placeholder="{{__('db.Please type raw material code and select')}}" class="form-control" />
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
                                                        <th>{{__('db.Available Quantity')}}</th>
                                                        <th>{{__('db.Adjustment Quantity')}}</th>
                                                        <th>{{__('db.Adjust Quantity')}}</th>
                                                        <th>{{__('db.action')}}</th>
                                                        <th><i class="dripicons-trash"></i></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                	@foreach($lims_raw_material_adjustment_data as $raw_material_adjustment_data)
                                                	<tr>
                                                	<?php
                                                	   $raw_material = DB::table('raw_materials')->find($raw_material_adjustment_data->product_id);
                                                	   $available_quantity = $raw_material->qty;
                                                	?>
                                                	<td>{{$raw_material->name}}</td>
                                                	<td>{{$raw_material->code}}</td>
                                                    <td>{{$raw_material_adjustment_data->unit_cost ?? $raw_material->cost}}<input type="hidden" name="unit_cost[]" value="{{$raw_material_adjustment_data->unit_cost ?? $raw_material->cost}}" /></td>
                                                    <td>{{ $available_quantity }}</td>
                                                    <td>{{ $raw_material_adjustment_data->qty }}</td>
                                                	<td><input type="number" class="form-control qty" name="qty[]" value="0" required step="any" /></td>
                                                	<td class="action">
                                                		<select name="action[]" class="form-control act-val">
                                                			@if($raw_material_adjustment_data->action == '+')
                                                			<option value="+">{{__("db.Addition")}}</option>
                                                			<option value="-">{{__("db.Subtraction")}}</option>
                                                			@else
                                                			<option value="-">{{__("db.Subtraction")}}</option><option value="+">{{__("db.Addition")}}</option>
                                                			@endif
                                                		</select>
                                                	</td>
                                                	<td><button type="button" class="ibtnDel btn btn-md btn-danger">{{__("db.delete")}}</button>
                                                	<input type="hidden" name="product_code[]" class="rawmaterial-code" value="{{$raw_material->code}}" />
                                                	<input type="hidden" class="rawmaterial-id" name="product_id[]" value="{{$raw_material->id}}" />
                                                    <input type="hidden" name="product_variant_id[]" value="" />
                                                	</td>
                                                	@endforeach
                                                	</tr>
                                                </tbody>
                                                <tfoot class="tfoot active">
                                                    <th colspan="5">{{__('db.Total')}}</th>
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
                                            <textarea rows="5" class="form-control" name="note">{{$lims_adjustment_data->note}}</textarea>
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
    $("ul#rawmaterial").siblings('a').attr('aria-expanded','true');
    $("ul#rawmaterial").addClass("show");
// array data depend on warehouse
var lims_rawmaterial_array = [];
var rawmaterial_code = [];
var rawmaterial_name = [];
var rawmaterial_qty = [];
var unit_cost = [];

var exist_code = [];
var exist_qty = [];

var rownumber = $('table.order-list tbody tr:last').index();

for(rowindex  =0; rowindex <= rownumber; rowindex++){
    exist_code.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text());
    var quantity = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val());
    exist_qty.push(quantity);
}

	$('.selectpicker').selectpicker({
	    style: 'btn-link',
	});
	//assigning value
	$('select[name="warehouse_id"]').val($('input[name="warehouse_id_hidden"]').val());
	$('.selectpicker').selectpicker('refresh');
	calculateTotal();

	$('#lims_rawmaterialcodeSearch').on('input', function(){
	    var warehouse_id = $('#warehouse_id').val();
	    temp_data = $('#lims_rawmaterialcodeSearch').val();

	    if(!warehouse_id){
	        $('#lims_rawmaterialcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
	        alert('Please select Warehouse!');
	    }
	});

	var id = $('#warehouse_id').val();
    $.get('{{ url("rawmaterial-adjustment/getrawmaterial") }}/' + id, function(data) {
        lims_rawmaterial_array = [];
        rawmaterial_code = data[0];
        rawmaterial_name = data[1];
        rawmaterial_qty = data[2];
        unit_cost = data[3];
        $.each(rawmaterial_code, function(index) {
            if(exist_code.includes(rawmaterial_code[index])) {
                pos = exist_code.indexOf(rawmaterial_code[index]);
                rawmaterial_qty[index] = rawmaterial_qty[index] + exist_qty[pos];
            }
            lims_rawmaterial_array.push(rawmaterial_code[index] + ' (' + rawmaterial_name[index] + ')'+ '|' + unit_cost[index] + '|' + rawmaterial_qty[index]);
        });
    });

	var lims_rawmaterialcodeSearch = $('#lims_rawmaterialcodeSearch');

	lims_rawmaterialcodeSearch.autocomplete({
	    source: function(request, response) {
	        var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
	        response($.grep(lims_rawmaterial_array, function(item) {
	            return matcher.test(item);
	        }));
	    },
        response: function(event, ui) {
            if (ui.content.length == 1) {
                var data = ui.content[0].value;
                $(this).autocomplete( "close" );
                rawmaterialSearch(data);
            };
        },
	    select: function(event, ui) {
	        var data = ui.item.value;
            rawmaterialSearch(data);
	    }
	});

	$('select[name="warehouse_id"]').on('change', function() {
	    var id = $('#warehouse_id').val();
	    $.get('{{ url("rawmaterial-adjustment/getrawmaterial") }}/' + id, function(data) {
	        lims_rawmaterial_array = [];
	        rawmaterial_code = data[0];
	        rawmaterial_name = data[1];
	        rawmaterial_qty = data[2];
	        unit_cost = data[3];
            $.each(rawmaterial_code, function(index) {
                lims_rawmaterial_array.push(rawmaterial_code[index] + ' (' + rawmaterial_name[index] + ')' + '|' + unit_cost[index] + '|' + rawmaterial_qty[index]);
            });
	    });
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

    function rawmaterialSearch(data){
        $.ajax({
            type: 'GET',
            url: '{{ url("rawmaterial-adjustment/lims_rawmaterial_search") }}',
            data: {
                data: data
            },
            success: function(data) {
                if(!data || data.length == 0) {
                    alert('Raw material not found!');
                    return;
                }
                var flag = 1;
                $(".rawmaterial-code").each(function(i) {
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
                    cols += '<td>' + data[0] + '</td>';
                    cols += '<td>' + data[1] + '</td>';
                    var cost = data[4] || 0;
                    cols += '<td>' + cost + '<input type="hidden" name="unit_cost[]" value="'+cost+'" /></td>';
                    cols += '<td>' + (data[5] || 0) + '<input type="hidden" name="available_quantity" value="'+(data[5] || 0)+'" /></td>';
                    cols += '<td>0</td>';
                    cols += '<td><input type="number" class="form-control qty" name="qty[]" value="1" required step="any"/></td>';
                    cols += '<td class="action"><select name="action[]" class="form-control act-val"><option value="-">{{__("db.Subtraction")}}</option><option value="+">{{__("db.Addition")}}</option></select></td>';
                    cols += '<td><button type="button" class="ibtnDel btn btn-md btn-danger">{{__("db.delete")}}</button></td>';
                    cols += '<input type="hidden" class="rawmaterial-code" name="product_code[]" value="' + data[1] + '"/>';
                    cols += '<input type="hidden" class="rawmaterial-id" name="product_id[]" value="' + data[2] + '"/>';
                    cols += '<input type="hidden" name="product_variant_id[]" value="" />';

                    newRow.append(cols);
                    $("table.order-list tbody").append(newRow);
                    $('.selectpicker').selectpicker('refresh');
                    rowindex = newRow.index();
                    calculateTotal();
                }
            }
        });
    }

	function checkQuantity(qty) {
	    var row_rawmaterial_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
        var action = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.act-val').val();
	    var pos = rawmaterial_code.indexOf(row_rawmaterial_code);
	    if (pos >= 0 && (qty > parseFloat(rawmaterial_qty[pos])) && (action == '-') ) {
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
