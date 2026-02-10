@extends('website.layout')

@section('title', __('website.contact_title') . ' - ' . __('website.site_name'))
@section('description', __('website.contact_subheading'))

@section('content')
<section class="py-16 md:py-24">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ __('website.contact_heading') }}</h1>
        <p class="text-lg text-gray-600 mb-12">{{ __('website.contact_subheading') }}</p>
        <div class="bg-gray-50 rounded-xl p-8 space-y-4">
            <p class="text-gray-600">
                <span class="font-medium text-gray-900">{{ __('website.contact_email') }}:</span> info@example.com
            </p>
            <p class="text-gray-600">
                <span class="font-medium text-gray-900">{{ __('website.contact_phone') }}:</span> +123 456 7890
            </p>
        </div>
    </div>
</section>
@endsection
