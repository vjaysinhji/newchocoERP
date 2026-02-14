@extends('backend.layout.main') @section('content')

@if($errors->any())
<div class="alert alert-danger alert-dismissible text-center">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <ul class="mb-0">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<section>
    <div class="container-fluid">
        <form method="post" action="{{ route('homepage-banners.update', $banner->id) }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">{{ __('db.Edit Hero Banner') }}</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('db.Title') }} (EN)</label>
                                        <input type="text" name="title" class="form-control" value="{{ old('title', $banner->title) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('db.Title') }} (AR)</label>
                                        <input type="text" name="title_ar" class="form-control" value="{{ old('title_ar', $banner->title_ar) }}" dir="rtl">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('db.Subtitle') }} (EN)</label>
                                        <textarea name="subtitle" class="form-control" rows="2">{{ old('subtitle', $banner->subtitle) }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('db.Subtitle') }} (AR)</label>
                                        <textarea name="subtitle_ar" class="form-control" rows="2" dir="rtl">{{ old('subtitle_ar', $banner->subtitle_ar) }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Button Text') }} (EN)</label>
                                        <input type="text" name="cta_text" class="form-control" value="{{ old('cta_text', $banner->cta_text ?? 'SHOP NOW') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Button Text') }} (AR)</label>
                                        <input type="text" name="cta_text_ar" class="form-control" value="{{ old('cta_text_ar', $banner->cta_text_ar ?? 'تسوق الآن') }}" dir="rtl">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Link') }}</label>
                                        <input type="text" name="cta_link" class="form-control" value="{{ old('cta_link', $banner->cta_link) }}">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{ __('db.Image') }}</label>
                                        @if($banner->image)
                                        <div class="mb-2"><img src="{{ url('frontend/images/hero/' . $banner->image) }}" style="max-width:150px;"></div>
                                        @endif
                                        <input type="file" name="image" class="form-control" accept="image/*">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Background Color') }}</label>
                                        <div class="input-group">
                                            <input type="color" name="bg_color_preview" value="{{ old('bg_color', $banner->bg_color ?? '#8B1538') }}" class="form-control" style="height:38px;padding:2px;width:50px;">
                                            <input type="text" name="bg_color" class="form-control" value="{{ old('bg_color', $banner->bg_color ?? '#8B1538') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Text Color') }}</label>
                                        <div class="input-group">
                                            <input type="color" name="text_color_preview" value="{{ old('text_color', $banner->text_color ?? '#FFFFFF') }}" class="form-control" style="height:38px;padding:2px;width:50px;">
                                            <input type="text" name="text_color" class="form-control" value="{{ old('text_color', $banner->text_color ?? '#FFFFFF') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Order') }}</label>
                                        <input type="number" name="order" class="form-control" value="{{ old('order', $banner->order ?? 0) }}">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{ __('db.Status') }}</label>
                                        <select name="status" class="form-control">
                                            <option value="1" @if(($banner->status ?? 1)==1) selected @endif>{{ __('db.Active') }}</option>
                                            <option value="0" @if(($banner->status ?? 1)==0) selected @endif>{{ __('db.Inactive') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('db.Update') }}</button>
            <a href="{{ route('homepage-banners.index') }}" class="btn btn-secondary">{{ __('db.Cancel') }}</a>
        </form>
    </div>
</section>

@push('scripts')
<script>
document.querySelectorAll('input[type="color"]').forEach(function(el){
    el.addEventListener('input', function(){
        this.nextElementSibling.value = this.value;
    });
});
</script>
@endpush
@endsection
