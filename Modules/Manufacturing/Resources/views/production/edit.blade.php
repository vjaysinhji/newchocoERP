@extends('backend.layout.main')

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Edit Production')}}</h4>
                    </div>
                    <div class="card-body">
                        @include('includes.session_message')
                        <form method="post" action="{{ route('productions.update', $lims_production_data->id) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.date')}}</label>
                                        <input type="text" name="created_at" class="form-control date" value="{{ $lims_production_data->created_at ? $lims_production_data->created_at->format('d-m-Y') : '' }}" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Production Warehouse')}}</label>
                                        <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true">
                                            @foreach($lims_warehouse_list as $warehouse)
                                            <option value="{{$warehouse->id}}" @if($lims_production_data->warehouse_id == $warehouse->id) selected @endif>{{$warehouse->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Batch/Lot Number</label>
                                        <input type="text" class="form-control" readonly value="{{ $lims_production_data->batch_lot_number ?? '-' }}" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Recipe</label>
                                        <input type="text" class="form-control" readonly value="{{ $lims_production_data->product->name ?? '-' }}" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Total Qty')}}</label>
                                        <input type="number" class="form-control" readonly value="{{ $lims_production_data->total_qty }}" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Expiry Date</label>
                                        <input type="text" name="expiry_date" id="expiry_date" class="form-control" value="{{ $lims_production_data->expiry_date ? $lims_production_data->expiry_date->format('d-m-Y') : '' }}" placeholder="dd-mm-yyyy" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Attach Document')}}</label>
                                        <input type="file" name="document" class="form-control" />
                                        @if($lims_production_data->document)
                                        <small class="text-muted">Current: <a href="{{ url('documents/production/'.$lims_production_data->document) }}" target="_blank">{{ $lims_production_data->document }}</a></small>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Production Overhead Type</label>
                                        <select name="production_overhead_type" id="production_overhead_type" class="form-control">
                                            <option value="fixed" @if(($lims_production_data->production_overhead_type ?? 'fixed') == 'fixed') selected @endif>Fixed Amount</option>
                                            <option value="percent" @if(($lims_production_data->production_overhead_type ?? '') == 'percent') selected @endif>Percentage (%)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Production Overhead</label>
                                        <input type="number" name="production_overhead_cost" id="production_overhead_cost" class="form-control" value="{{ $lims_production_data->production_overhead_cost ?? 0 }}" min="0" step="any" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Production Cost')}}</label>
                                        <input type="number" name="production_cost" class="form-control" value="{{ $lims_production_data->production_cost ?? 0 }}" step="any" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Shipping Cost')}}</label>
                                        <input type="number" name="shipping_cost" class="form-control" value="{{ $lims_production_data->shipping_cost ?? 0 }}" step="any" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{__('db.Note')}}</label>
                                        <textarea rows="4" class="form-control" name="note">{{ $lims_production_data->note }}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">{{__('db.update')}}</button>
                                        <a href="{{ route('productions.index') }}" class="btn btn-secondary">{{__('db.Cancel')}}</a>
                                    </div>
                                </div>
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
    $('.date').datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true,
        todayHighlight: true
    });
    $('#expiry_date').datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true,
        todayHighlight: true,
        startDate: new Date()
    });
    $('.selectpicker').selectpicker('refresh');
</script>
@endpush
