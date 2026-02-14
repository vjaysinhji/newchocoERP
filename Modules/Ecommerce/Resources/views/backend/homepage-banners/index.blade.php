@extends('backend.layout.main') @section('content')

@if(session()->has('message'))
<div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session()->get('message') }}</div>
@endif

<section>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>{{ __('db.Homepage Hero Banners') }}</h4>
                        <a href="{{ route('homepage-banners.create') }}" class="btn btn-primary">{{ __('db.Add') }}</a>
                    </div>
                    <div class="card-body">
                        @if($banners->isEmpty())
                        <p class="text-muted">{{ __('db.No banners yet. Add your first hero banner.') }}</p>
                        @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('db.Order') }}</th>
                                        <th>{{ __('db.Image') }}</th>
                                        <th>{{ __('db.Title') }} (EN)</th>
                                        <th>{{ __('db.Title') }} (AR)</th>
                                        <th>{{ __('db.Status') }}</th>
                                        <th>{{ __('db.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($banners as $banner)
                                    <tr>
                                        <td>{{ $banner->order }}</td>
                                        <td>
                                            @if($banner->image)
                                            <img src="{{ url('frontend/images/hero/' . $banner->image) }}" alt="" style="max-width:80px;height:auto;">
                                            @else
                                            <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ Str::limit($banner->title, 40) }}</td>
                                        <td>{{ Str::limit($banner->title_ar, 40) }}</td>
                                        <td>
                                            @if($banner->status)
                                            <span class="badge badge-success">{{ __('db.Active') }}</span>
                                            @else
                                            <span class="badge badge-secondary">{{ __('db.Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('homepage-banners.edit', $banner->id) }}" class="btn btn-sm btn-info">{{ __('db.Edit') }}</a>
                                            <a href="{{ route('homepage-banners.destroy', $banner->id) }}" onclick="return confirm('{{ __('db.Are you sure?') }}')" class="btn btn-sm btn-danger">{{ __('db.Delete') }}</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
