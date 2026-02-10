@extends('website.layout')

@section('title', __('website.about_title') . ' - ' . __('website.site_name'))
@section('description', __('website.about_intro'))

@section('content')
<section class="py-16 md:py-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-6">{{ __('website.about_heading') }}</h1>
        <p class="text-xl text-gray-600 mb-6">{{ __('website.about_intro') }}</p>
        <p class="text-gray-600 mb-6">{{ __('website.about_para1') }}</p>
        <p class="text-gray-600">{{ __('website.about_para2') }}</p>
    </div>
</section>
@endsection
