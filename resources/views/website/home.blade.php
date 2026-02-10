@extends('website.layout')

@section('title', __('website.home') . ' - ' . __('website.site_name'))
@section('description', __('website.tagline'))

@section('content')
<section class="relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
        <div class="text-center">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 mb-4">
                {{ __('website.welcome') }} <span class="text-primary">{{ __('website.site_name') }}</span>
            </h1>
            <p class="text-lg sm:text-xl text-gray-600 max-w-2xl mx-auto mb-8">
                {{ __('website.tagline') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/login') }}" class="inline-flex items-center justify-center px-8 py-3 text-base font-medium rounded-lg text-white bg-primary hover:bg-purple-700 transition shadow-lg">
                    {{ __('website.admin_login') }}
                </a>
                <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center px-8 py-3 text-base font-medium rounded-lg text-primary bg-purple-50 hover:bg-purple-100 transition border border-primary">
                    {{ __('website.dashboard') }}
                </a>
            </div>
        </div>
    </div>
</section>

<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-center text-gray-900 mb-12">{{ __('website.why_choose_us') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('website.sales_pos') }}</h3>
                <p class="text-gray-600">{{ __('website.sales_pos_desc') }}</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('website.inventory') }}</h3>
                <p class="text-gray-600">{{ __('website.inventory_desc') }}</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition sm:col-span-2 lg:col-span-1">
                <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('website.reports') }}</h3>
                <p class="text-gray-600">{{ __('website.reports_desc') }}</p>
            </div>
        </div>
    </div>
</section>
@endsection
