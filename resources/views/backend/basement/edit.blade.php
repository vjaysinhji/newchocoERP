@extends('backend.layout.main')

@section('content')

    <x-success-message key="edit_message" />

    <section class="forms">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4>{{ __('db.Update Warehouse Store') }}</h4>
                        </div>

                        <x-error-message key="not_permitted" />

                        <div class="card-body">
                            <p class="italic">
                                <small>{{ __('db.The field labels marked with * are required input fields') }}.</small></p>
                            <form id="basement-form">
                                <input type="hidden" name="id" value="{{ $lims_basement_data->id }}" />
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('db.warehouse_item_name') }} * </label>
                                            <input type="text" name="name" value="{{ $lims_basement_data->name }}"
                                                required class="form-control">
                                            <span class="validation-msg" id="name-error"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('db.Barcode Symbology') }} * </label>
                                            <div class="input-group">
                                                <select name="barcode_symbology" required class="form-control selectpicker">
                                                    <option value="C128" {{ ($lims_basement_data->barcode_symbology ?? '') == 'C128' ? 'selected' : '' }}>Code 128</option>
                                                    <option value="C39" {{ ($lims_basement_data->barcode_symbology ?? '') == 'C39' ? 'selected' : '' }}>Code 39</option>
                                                    <option value="UPCA" {{ ($lims_basement_data->barcode_symbology ?? '') == 'UPCA' ? 'selected' : '' }}>UPC-A</option>
                                                    <option value="UPCE" {{ ($lims_basement_data->barcode_symbology ?? '') == 'UPCE' ? 'selected' : '' }}>UPC-E</option>
                                                    <option value="EAN8" {{ ($lims_basement_data->barcode_symbology ?? '') == 'EAN8' ? 'selected' : '' }}>EAN-8</option>
                                                    <option value="EAN13" {{ ($lims_basement_data->barcode_symbology ?? '') == 'EAN13' ? 'selected' : '' }}>EAN-13</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('db.Code') }} * </label>
                                            <div class="input-group">
                                                <input type="text" name="code" id="code"
                                                    value="{{ $lims_basement_data->code }}" class="form-control" required>
                                                <div class="input-group-append">
                                                    <button id="genbutton" type="button" class="btn btn-sm btn-default"
                                                        title="{{ __('db.Generate') }}"><i
                                                            class="fa fa-refresh"></i></button>
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
                                                    <option value="{{$category->id}}" {{ $lims_basement_data->category_id == $category->id ? 'selected' : '' }}>{{$category->name}}</option>
                                                @endforeach
                                              </select>
                                          </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Unit')}}</label>
                                            <div class="input-group pos">
                                              <select name="unit_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Unit...">
                                                @foreach($lims_unit_list as $unit)
                                                    <option value="{{$unit->id}}" {{ $lims_basement_data->unit_id == $unit->id ? 'selected' : '' }}>{{$unit->unit_name}}</option>
                                                @endforeach
                                              </select>
                                          </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Cost')}}</label>
                                            <input type="number" name="cost" value="{{ $lims_basement_data->cost }}" class="form-control" step="any">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Alert Quantity')}}</label>
                                            <input type="number" name="alert_quantity" value="{{ $lims_basement_data->alert_quantity }}" class="form-control" step="any">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{__('db.Image')}}</label>
                                            @if($lims_basement_data->image && $lims_basement_data->image != 'zummXD2dvAtI.png')
                                                <div class="mb-2">
                                                    @php
                                                        $images = explode(',', $lims_basement_data->image);
                                                    @endphp
                                                    @foreach($images as $img)
                                                        <img src="{{ asset('images/basement/' . $img) }}" height="50" class="mr-2">
                                                    @endforeach
                                                </div>
                                            @endif
                                            <input type="file" name="image[]" class="form-control" multiple accept="image/*">
                                            <input type="hidden" name="prev_img" value="{{ $lims_basement_data->image }}">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{__('db.Details')}}</label>
                                            <textarea name="product_details" class="form-control" rows="3">{{ $lims_basement_data->product_details }}</textarea>
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
                url:"{{ route('warehouse-stores.update') }}",
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
                        alert(response?.message || 'Update failed');
                    }
                },
                error:function(xhr, status, error) {
                    $('#submit-btn').attr('disabled',false).html('{{__("db.submit")}}');
                    console.error('Update Error:', xhr.responseJSON);
                    
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
                        alert('{{__("db.Failed to update warehouse store. Please try again")}}');
                    }
                },
            });
        }
    });
</script>
@endpush
