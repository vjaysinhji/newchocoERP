@extends('backend.layout.main')

@section('content')

    <x-success-message key="edit_message" />

    <section class="forms">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4>{{ __('db.Update Cold Storage') }}</h4>
                        </div>

                        <x-error-message key="not_permitted" />

                        <div class="card-body">
                            <p class="italic">
                                <small>{{ __('db.The field labels marked with * are required input fields') }}.</small></p>
                            <form id="coldstorage-form">
                                <input type="hidden" name="id" value="{{ $lims_coldstorage_data->id }}" />
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('db.Cold Storage Name') }} * </label>
                                            <input type="text" name="name" value="{{ $lims_coldstorage_data->name }}"
                                                required class="form-control">
                                            <span class="validation-msg" id="name-error"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Name (Arabic)</label>
                                            <input type="text" name="name_arabic"
                                                value="{{ $lims_coldstorage_data->name_arabic ?? '' }}" class="form-control">
                                            <span class="validation-msg" id="name_arabic-error"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('db.Code') }} * </label>
                                            <div class="input-group">
                                                <input type="text" name="code" id="code"
                                                    value="{{ $lims_coldstorage_data->code }}" class="form-control" required>
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
                                            <label>{{ __('db.Barcode Symbology') }} * </label>
                                            <select name="barcode_symbology" required class="form-control selectpicker">
                                                <option value="C128" {{ $lims_coldstorage_data->barcode_symbology == 'C128' ? 'selected' : '' }}>Code 128</option>
                                                <option value="C39" {{ $lims_coldstorage_data->barcode_symbology == 'C39' ? 'selected' : '' }}>Code 39</option>
                                                <option value="UPCA" {{ $lims_coldstorage_data->barcode_symbology == 'UPCA' ? 'selected' : '' }}>UPC-A</option>
                                                <option value="UPCE" {{ $lims_coldstorage_data->barcode_symbology == 'UPCE' ? 'selected' : '' }}>UPC-E</option>
                                                <option value="EAN8" {{ $lims_coldstorage_data->barcode_symbology == 'EAN8' ? 'selected' : '' }}>EAN-8</option>
                                                <option value="EAN13" {{ $lims_coldstorage_data->barcode_symbology == 'EAN13' ? 'selected' : '' }}>EAN-13</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Brand')}}</strong> </label>
                                            <div class="input-group pos">
                                              <select name="brand_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Brand...">
                                                <option value="">No Brand</option>
                                                @foreach($lims_brand_list as $brand)
                                                    <option value="{{$brand->id}}" {{ $lims_coldstorage_data->brand_id == $brand->id ? 'selected' : '' }}>{{$brand->title}}</option>
                                                @endforeach
                                              </select>
                                          </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.category')}} *</strong> </label>
                                            <div class="input-group pos">
                                              <select name="category_id" required class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Category...">
                                                @foreach($lims_category_list as $category)
                                                    <option value="{{$category->id}}" {{ $lims_coldstorage_data->category_id == $category->id ? 'selected' : '' }}>{{$category->name}}</option>
                                                @endforeach
                                              </select>
                                          </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Unit')}} *</strong> </label>
                                            <div class="input-group pos">
                                              <select name="unit_id" required class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Unit...">
                                                @foreach($lims_unit_list as $unit)
                                                    <option value="{{$unit->id}}" {{ $lims_coldstorage_data->unit_id == $unit->id ? 'selected' : '' }}>{{$unit->unit_name}}</option>
                                                @endforeach
                                              </select>
                                          </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Cost')}} *</strong> </label>
                                            <input type="number" name="cost" value="{{ $lims_coldstorage_data->cost }}" required class="form-control" step="any">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Price')}} *</strong> </label>
                                            <input type="number" name="price" value="{{ $lims_coldstorage_data->price }}" required class="form-control" step="any">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Alert Quantity')}}</strong> </label>
                                            <input type="number" name="alert_quantity" value="{{ $lims_coldstorage_data->alert_quantity }}" class="form-control" step="any">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Tax')}}</label>
                                            <div class="input-group pos">
                                            <select name="tax_id" class="selectpicker form-control">
                                                <option value="">No Tax</option>
                                                @foreach($lims_tax_list as $tax)
                                                    <option value="{{$tax->id}}" {{ $lims_coldstorage_data->tax_id == $tax->id ? 'selected' : '' }}>{{$tax->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Tax Method')}}</strong> </label>
                                            <select name="tax_method" class="form-control selectpicker">
                                                <option value="1" {{ $lims_coldstorage_data->tax_method == 1 ? 'selected' : '' }}>{{__('db.Exclusive')}}</option>
                                                <option value="2" {{ $lims_coldstorage_data->tax_method == 2 ? 'selected' : '' }}>{{__('db.Inclusive')}}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{__('db.Image')}}</strong> </label>
                                            @if($lims_coldstorage_data->image && $lims_coldstorage_data->image != 'zummXD2dvAtI.png')
                                                <div class="mb-2">
                                                    @php
                                                        $images = explode(',', $lims_coldstorage_data->image);
                                                    @endphp
                                                    @foreach($images as $img)
                                                        <img src="{{ asset('images/coldstorage/' . $img) }}" height="50" class="mr-2">
                                                    @endforeach
                                                </div>
                                            @endif
                                            <input type="file" name="image[]" class="form-control" multiple accept="image/*">
                                            <input type="hidden" name="prev_img" value="{{ $lims_coldstorage_data->image }}">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{__('db.Details')}}</label>
                                            <textarea name="product_details" class="form-control" rows="3">{{ $lims_coldstorage_data->product_details }}</textarea>
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
        $.get('{{ route("coldstorage.gencode") }}', function(data) {
            $("#code").val(data);
        });
    });

    $('#coldstorage-form').on('submit', function(e) {
        e.preventDefault();
        if ($("#coldstorage-form").valid()) {
            $('#submit-btn').attr('disabled','true').html('<span class="spinner-border text-light" role="status"></span> {{__("db.Saving")}}...');
            var formData = new FormData();
            var data = $("#coldstorage-form").serializeArray();
            $.each(data, function (key, el) {
                formData.append(el.name, el.value);
            });
            var images = $('#coldstorage-form input[name="image[]"]')[0].files;
            for (var i = 0; i < images.length; i++) {
                formData.append('image[]', images[i]);
            }

            $.ajax({
                type:'POST',
                url:"{{ route('coldstorages.updateData') }}",
                data: formData,
                contentType: false,
                processData: false,
                success:function(response) {
                    location.href = '{{ route("coldstorages.index") }}';
                },
                error:function(response) {
                    $('#submit-btn').attr('disabled',false).html('{{__("db.submit")}}');
                    if(response.responseJSON.errors.name) {
                        $("#name-error").text(response.responseJSON.errors.name[0]);
                    }
                    if(response.responseJSON.errors.code) {
                        $("#code-error").text(response.responseJSON.errors.code[0]);
                    }
                },
            });
        }
    });
</script>
@endpush
