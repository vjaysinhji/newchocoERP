@extends('backend.layout.main')

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{ __('db.add_warehouse_item') }}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
                        <form id="basement-form">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.warehouse_item_name') }} *</label>
                                        <input type="text" name="name" class="form-control" id="name" required>
                                        <span class="validation-msg" id="name-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Barcode Symbology') }} *</label>
                                        <div class="input-group">
                                            <select name="barcode_symbology" required class="form-control selectpicker">
                                                <option value="C128">Code 128</option>
                                                <option value="C39">Code 39</option>
                                                <option value="UPCA">UPC-A</option>
                                                <option value="UPCE" selected>UPC-E</option>
                                                <option value="EAN8">EAN-8</option>
                                                <option value="EAN13">EAN-13</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Code')}} *</label>
                                        <div class="input-group">
                                            <input type="text" name="code" class="form-control" id="code" required>
                                            <div class="input-group-append">
                                                <button id="genbutton" type="button" class="btn btn-sm btn-default" title="{{__('db.Generate')}}"><i class="fa fa-refresh"></i></button>
                                            </div>
                                        </div>
                                        <span class="validation-msg" id="code-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.category')}}</label>
                                        <div class="input-group pos">
                                          <select name="category_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Category...">
                                            @foreach($lims_category_list as $category)
                                                <option value="{{$category->id}}">{{$category->name}}</option>
                                            @endforeach
                                          </select>
                                      </div>
                                      <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Unit')}}</label>
                                        <div class="input-group pos">
                                          <select name="unit_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Unit...">
                                            @foreach($lims_unit_list as $unit)
                                                <option value="{{$unit->id}}">{{$unit->unit_name}}</option>
                                            @endforeach
                                          </select>
                                      </div>
                                      <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Cost')}}</label>
                                        <input type="number" name="cost" class="form-control" step="any">
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Alert Quantity')}}</label>
                                        <input type="number" name="alert_quantity" class="form-control" step="any">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{__('db.Image')}}</label>
                                        <input type="file" name="image[]" class="form-control" multiple accept="image/*">
                                        <span class="validation-msg" id="image-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{__('db.Details')}}</label>
                                        <textarea name="product_details" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mt-3">
                                <button type="submit" id="submit-btn" class="btn btn-primary">{{__('db.submit')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    $("#genbutton").on("click", function() {
        $.get('{{ route("warehouse-store.gencode") }}', function(data) {
            $("#code").val(data);
        });
    });

    $('#basement-form').on('submit', function(e) {
        e.preventDefault();
        
        // Refresh selectpicker to get current values
        $('.selectpicker').selectpicker('refresh');
        
        if ($("#basement-form").valid()) {
            $('#submit-btn').attr('disabled','true').html('<span class="spinner-border text-light" role="status"></span> {{__("db.Saving")}}...');
            var formData = new FormData();
            var data = $("#basement-form").serializeArray();
            $.each(data, function (key, el) {
                formData.append(el.name, el.value);
            });
            
            var categoryId = $('#basement-form select[name="category_id"]').val() || '';
            var unitId = $('#basement-form select[name="unit_id"]').val() || '';
            var barcodeSymbology = $('#basement-form select[name="barcode_symbology"]').val() || 'UPCE';
            formData.set('category_id', categoryId);
            formData.set('unit_id', unitId);
            formData.set('barcode_symbology', barcodeSymbology);
            
            var images = $('#basement-form input[name="image[]"]')[0].files;
            for (var i = 0; i < images.length; i++) {
                formData.append('image[]', images[i]);
            }

            $.ajax({
                type:'POST',
                url:"{{ route('warehouse-stores.store') }}",
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success:function(response) {
                    if(response && response.success) {
                        location.href = '{{ route("warehouse-stores.index") }}';
                    } else {
                        $('#submit-btn').attr('disabled',false).html('{{__("db.submit")}}');
                        alert(response?.message || 'Create failed');
                    }
                },
                error:function(xhr, status, error) {
                    $('#submit-btn').attr('disabled',false).html('{{__("db.submit")}}');
                    console.error('Create Error:', xhr.responseJSON);
                    
                    if(xhr.responseJSON) {
                        if(xhr.responseJSON.errors) {
                            if(xhr.responseJSON.errors.name) {
                                $("#name-error").text(xhr.responseJSON.errors.name[0]);
                            }
                            if(xhr.responseJSON.errors.code) {
                                $("#code-error").text(xhr.responseJSON.errors.code[0]);
                            }
                            if(xhr.responseJSON.errors.category_id) {
                                alert('Category: ' + xhr.responseJSON.errors.category_id[0]);
                            }
                            if(xhr.responseJSON.errors.unit_id) {
                                alert('Unit: ' + xhr.responseJSON.errors.unit_id[0]);
                            }
                            if(xhr.responseJSON.errors.cost) {
                                alert('Cost: ' + xhr.responseJSON.errors.cost[0]);
                            }
                        }
                        if(xhr.responseJSON.message) {
                            alert(xhr.responseJSON.message);
                        }
                    } else {
                        alert('{{__("db.Failed to create warehouse store. Please try again")}}');
                    }
                },
            });
        }
    });
</script>
@endpush
