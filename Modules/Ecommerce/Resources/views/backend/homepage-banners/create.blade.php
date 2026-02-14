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
        <form method="post" action="{{ route('homepage-banners.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">{{ __('db.Add Hero Banner') }}</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('db.Title') }} ({{ __('db.English') }})</label>
                                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="ONE GIFT, A THOUSAND WORDS">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('db.Title') }} ({{ __('db.Arabic') }})</label>
                                        <input type="text" name="title_ar" class="form-control" value="{{ old('title_ar') }}" dir="rtl" placeholder="هدية واحدة، ألف كلمة">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('db.Subtitle') }} (EN)</label>
                                        <textarea name="subtitle" class="form-control" rows="2">{{ old('subtitle') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('db.Subtitle') }} (AR)</label>
                                        <textarea name="subtitle_ar" class="form-control" rows="2" dir="rtl">{{ old('subtitle_ar') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Button Text') }} (EN)</label>
                                        <input type="text" name="cta_text" class="form-control" value="{{ old('cta_text', 'SHOP NOW') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Button Text') }} (AR)</label>
                                        <input type="text" name="cta_text_ar" class="form-control" value="{{ old('cta_text_ar', 'تسوق الآن') }}" dir="rtl">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Link') }}</label>
                                        <input type="text" name="cta_link" class="form-control" value="{{ old('cta_link') }}" placeholder="/shop/valentines">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{ __('db.Image') }}</label>
                                        <input type="file" name="image" class="form-control" accept="image/*">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Background Color') }}</label>
                                        <div class="input-group">
                                            <input type="color" name="bg_color_preview" value="{{ old('bg_color', '#8B1538') }}" class="form-control" style="height:38px;padding:2px;width:50px;">
                                            <input type="text" name="bg_color" class="form-control" value="{{ old('bg_color', '#8B1538') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Text Color') }}</label>
                                        <div class="input-group">
                                            <input type="color" name="text_color_preview" value="{{ old('text_color', '#FFFFFF') }}" class="form-control" style="height:38px;padding:2px;width:50px;">
                                            <input type="text" name="text_color" class="form-control" value="{{ old('text_color', '#FFFFFF') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Order') }}</label>
                                        <input type="number" name="order" class="form-control" value="{{ old('order', 0) }}">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{ __('db.Status') }}</label>
                                        <select name="status" class="form-control">
                                            <option value="1">{{ __('db.Active') }}</option>
                                            <option value="0">{{ __('db.Inactive') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('db.Save') }}</button>
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
