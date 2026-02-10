@extends('backend.layout.main')

@section('content')

    <x-success-message key="edit_message" />

    <section class="forms">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4>{{ __('db.Update Raw Material') }}</h4>
                        </div>

                        <x-error-message key="not_permitted" />

                        <div class="card-body">
                            <p class="italic">
                                <small>{{ __('db.The field labels marked with * are required input fields') }}.</small></p>
                            <form id="rawmaterial-form">
                                <input type="hidden" name="id" value="{{ $lims_rawmaterial_data->id }}" />
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('db.Raw Material Name') }} * </label>
                                            <input type="text" name="name" value="{{ $lims_rawmaterial_data->name }}"
                                                required class="form-control">
                                            <span class="validation-msg" id="name-error"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('db.Code') }} * </label>
                                            <div class="input-group">
                                                <input type="text" name="code" id="code"
                                                    value="{{ $lims_rawmaterial_data->code }}" class="form-control" required>
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
                                            <label>{{ __('db.Barcode') }}</label>
                                            <input type="text" name="barcode_symbology" value="{{ $lims_rawmaterial_data->barcode_symbology }}" class="form-control" placeholder="Barcode">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.category')}} *</label>
                                            <div class="input-group pos">
                                              <select name="category_id" required class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Category...">
                                                @foreach($lims_category_list as $category)
                                                    <option value="{{$category->id}}" {{ $lims_rawmaterial_data->category_id == $category->id ? 'selected' : '' }}>{{$category->name}}</option>
                                                @endforeach
                                              </select>
                                          </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Unit')}} *</label>
                                            <div class="input-group pos">
                                              <select name="unit_id" required class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Unit...">
                                                @foreach($lims_unit_list as $unit)
                                                    <option value="{{$unit->id}}" {{ $lims_rawmaterial_data->unit_id == $unit->id ? 'selected' : '' }}>{{$unit->unit_name}}</option>
                                                @endforeach
                                              </select>
                                          </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Cost')}} *</label>
                                            <input type="number" name="cost" value="{{ $lims_rawmaterial_data->cost }}" required class="form-control" step="any">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Alert Quantity')}}</label>
                                            <input type="number" name="alert_quantity" value="{{ $lims_rawmaterial_data->alert_quantity }}" class="form-control" step="any">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{__('db.Image')}}</label>
                                            @if($lims_rawmaterial_data->image && $lims_rawmaterial_data->image != 'zummXD2dvAtI.png')
                                                <div class="mb-2">
                                                    @php
                                                        $images = explode(',', $lims_rawmaterial_data->image);
                                                    @endphp
                                                    @foreach($images as $img)
                                                        <img src="{{ asset('images/rawmaterial/' . $img) }}" height="50" class="mr-2">
                                                    @endforeach
                                                </div>
                                            @endif
                                            <input type="file" name="image[]" class="form-control" multiple accept="image/*">
                                            <input type="hidden" name="prev_img" value="{{ $lims_rawmaterial_data->image }}">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{__('db.Details')}}</label>
                                            <textarea name="product_details" class="form-control" rows="3">{{ $lims_rawmaterial_data->product_details }}</textarea>
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
        $.get('{{ route("rawmaterial.gencode") }}', function(data) {
            $("#code").val(data);
        });
    });

    $('#rawmaterial-form').on('submit', function(e) {
        e.preventDefault();
        
        // Refresh selectpicker to get current values
        $('.selectpicker').selectpicker('refresh');
        
        // Validate selectpicker fields manually
        var categoryId = $('#rawmaterial-form select[name="category_id"]').val();
        var unitId = $('#rawmaterial-form select[name="unit_id"]').val();
        
        if (!categoryId || categoryId === '') {
            alert('Please select a category');
            return false;
        }
        if (!unitId || unitId === '') {
            alert('Please select a unit');
            return false;
        }
        
        if ($("#rawmaterial-form").valid()) {
            $('#submit-btn').attr('disabled','true').html('<span class="spinner-border text-light" role="status"></span> {{__("db.Saving")}}...');
            var formData = new FormData();
            var data = $("#rawmaterial-form").serializeArray();
            $.each(data, function (key, el) {
                formData.append(el.name, el.value);
            });
            
            // Get selectpicker values explicitly and ensure they're set
            formData.set('category_id', categoryId);
            formData.set('unit_id', unitId);
            
            var images = $('#rawmaterial-form input[name="image[]"]')[0].files;
            for (var i = 0; i < images.length; i++) {
                formData.append('image[]', images[i]);
            }

            $.ajax({
                type:'POST',
                url:"{{ route('rawmaterials.updateData') }}",
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success:function(response) {
                    if(response && response.success) {
                        location.href = '{{ route("rawmaterials.index") }}';
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
                        alert('{{__("db.Failed to update raw material. Please try again")}}');
                    }
                },
            });
        }
    });
</script>
@endpush
