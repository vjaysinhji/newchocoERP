@extends('ecommerce::frontend.layout.main')

@section('title') {{ $ecommerce_setting->site_title ?? '' }} @endsection
@section('description') {{ '' }} @endsection

@section('content')

@php
    $isRtl = !empty($ecommerce_setting->is_rtl);
    $lang = app()->getLocale();
@endphp

{{-- Hero Banners --}}
@if(isset($hero_banners) && $hero_banners->isNotEmpty())
<section class="relative overflow-hidden" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    @foreach($hero_banners as $banner)
    <div class="relative min-h-[400px] md:min-h-[500px] lg:min-h-[550px] flex items-center" style="background-color: {{ $banner->bg_color ?? '#8B1538' }};">
        <div class="container mx-auto px-4 md:px-6 lg:px-8 py-12 md:py-16">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <div class="text-left {{ $isRtl ? 'lg:text-right' : '' }}">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold uppercase tracking-wide mb-4" style="color: {{ $banner->text_color ?? '#FFFFFF' }};">
                        {{ $lang == 'ar' && !empty($banner->title_ar) ? $banner->title_ar : ($banner->title ?? '') }}
                    </h1>
                    @if(!empty($banner->subtitle) || !empty($banner->subtitle_ar))
                    <p class="text-lg md:text-xl mb-6 opacity-95" style="color: {{ $banner->text_color ?? '#FFFFFF' }};">
                        {{ $lang == 'ar' && !empty($banner->subtitle_ar) ? $banner->subtitle_ar : ($banner->subtitle ?? '') }}
                    </p>
                    @endif
                    @if(!empty($banner->cta_link))
                    <a href="{{ url($banner->cta_link) }}" class="inline-block px-8 py-3 font-semibold uppercase tracking-wider text-white transition hover:opacity-90" style="background-color: {{ $ecommerce_setting->cta_bg_color ?? '#000000' }};">
                        {{ $lang == 'ar' && !empty($banner->cta_text_ar) ? $banner->cta_text_ar : ($banner->cta_text ?? 'SHOP NOW') }}
                    </a>
                    @endif
                </div>
                @if(!empty($banner->image))
                <div class="hidden lg:flex justify-center {{ $isRtl ? 'lg:order-first' : '' }}">
                    <img src="{{ url('frontend/images/hero/' . $banner->image) }}" alt="" class="max-h-[400px] object-contain">
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</section>
@endif

{{-- Featured Products Section --}}
@if(isset($featured_products) && $featured_products->isNotEmpty())
<section class="py-12 md:py-16 bg-gray-50" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <div class="container mx-auto px-4 md:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-center mb-8 md:mb-12">
            {{ $lang == 'ar' ? 'منتجات مميزة' : 'Popular Gifts' }}
        </h2>
        <div class="relative">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 md:gap-6 product-grid-chocolat">
                @foreach($featured_products as $product)
                <a href="{{ url('product/' . ($product->slug ?? $product->name) . '/' . $product->id) }}" class="group block bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-lg transition-shadow">
                    <div class="aspect-square relative bg-gray-100">
                        @if(!empty($product->image))
                        @php $img = is_string($product->image) ? explode(',', $product->image)[0] : $product->image; @endphp
                        <img src="{{ url('images/product/large/' . $img) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                        <div class="w-full h-full flex items-center justify-center text-gray-300 text-4xl">{{ Str::limit($product->name, 1) }}</div>
                        @endif
                        @if(($product->promotion ?? 0) == 1 && (empty($product->last_date) || ($product->last_date ?? '') > date('Y-m-d')))
                        <span class="absolute top-2 {{ $isRtl ? 'right' : 'left' }}-2 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded">
                            -{{ $product->price > 0 ? round(($product->price - ($product->promotion_price ?? $product->price)) / $product->price * 100) : 0 }}%
                        </span>
                        @endif
                    </div>
                    <div class="p-3 md:p-4">
                        <h3 class="text-sm md:text-base font-medium text-gray-800 line-clamp-2 min-h-[2.5em]">
                            {{ $lang == 'ar' && !empty($product->name_ar) ? $product->name_ar : $product->name }}
                        </h3>
                        <div class="mt-2 flex items-center gap-2">
                            @if(($product->promotion ?? 0) == 1 && (empty($product->last_date) || ($product->last_date ?? '') > date('Y-m-d')))
                            <span class="font-bold" style="color: var(--theme-color, #8B1538);">
                                @if($general_setting->currency_position == 'prefix')
                                {{ $currency->symbol ?? '' }} {{ $product->promotion_price ?? $product->price }}
                                @else
                                {{ $product->promotion_price ?? $product->price }} {{ $currency->symbol ?? '' }}
                                @endif
                            </span>
                            <span class="text-gray-400 text-sm line-through">
                                @if($general_setting->currency_position == 'prefix')
                                {{ $currency->symbol ?? '' }} {{ $product->price }}
                                @else
                                {{ $product->price }} {{ $currency->symbol ?? '' }}
                                @endif
                            </span>
                            @else
                            <span class="font-bold" style="color: var(--theme-color, #8B1538);">
                                @if($general_setting->currency_position == 'prefix')
                                {{ $currency->symbol ?? '' }} {{ $product->price }}
                                @else
                                {{ $product->price }} {{ $currency->symbol ?? '' }}
                                @endif
                            </span>
                            @endif
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        <div class="text-center mt-8">
            <a href="{{ url('shop') }}" class="inline-block px-8 py-3 font-semibold uppercase text-white transition hover:opacity-90" style="background-color: {{ $ecommerce_setting->cta_bg_color ?? '#000000' }};">
                {{ $lang == 'ar' ? 'تسوق الكل' : 'Shop All Gifts' }}
            </a>
        </div>
    </div>
</section>
@endif

{{-- Secondary Banner (Easter-style) - use second hero banner if exists --}}
@if(isset($hero_banners) && $hero_banners->count() >= 2)
@php $easterBanner = $hero_banners[1]; @endphp
<section class="py-12 md:py-20" style="background-color: {{ $easterBanner->bg_color ?? '#E8DCC8' }};" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <div class="container mx-auto px-4 md:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            <div class="text-left {{ $isRtl ? 'lg:order-2 lg:text-right' : '' }}">
                <h2 class="text-2xl md:text-4xl font-bold uppercase mb-4" style="color: {{ $easterBanner->text_color ?? '#333' }};">
                    {{ $lang == 'ar' && !empty($easterBanner->title_ar) ? $easterBanner->title_ar : ($easterBanner->title ?? '') }}
                </h2>
                <p class="text-lg mb-6 opacity-90" style="color: {{ $easterBanner->text_color ?? '#333' }};">
                    {{ $lang == 'ar' && !empty($easterBanner->subtitle_ar) ? $easterBanner->subtitle_ar : ($easterBanner->subtitle ?? '') }}
                </p>
                @if(!empty($easterBanner->cta_link))
                <a href="{{ url($easterBanner->cta_link) }}" class="inline-block px-8 py-3 font-semibold uppercase text-white transition hover:opacity-90" style="background-color: {{ $ecommerce_setting->cta_bg_color ?? '#000000' }};">
                    {{ $lang == 'ar' && !empty($easterBanner->cta_text_ar) ? $easterBanner->cta_text_ar : ($easterBanner->cta_text ?? 'SHOP NOW') }}
                </a>
                @endif
            </div>
            @if(!empty($easterBanner->image))
            <div class="{{ $isRtl ? 'lg:order-1' : '' }}">
                <img src="{{ url('frontend/images/hero/' . $easterBanner->image) }}" alt="" class="max-h-[350px] mx-auto object-contain">
            </div>
            @endif
        </div>
    </div>
</section>
@endif

{{-- Widgets (from page builder) --}}
@if(isset($widgets))
@foreach($widgets as $widget)
@if($widget->name == 'product-collection-widget')
@include('ecommerce::frontend.includes.product-collection-widget')
@endif
@if($widget->name == 'product-category-widget')
@include('ecommerce::frontend.includes.product-category-widget')
@endif
@endforeach
@endif

{{-- Recently Viewed --}}
@if(isset($recently_viewed) && count($recently_viewed) > 0)
@include('ecommerce::frontend.includes.recently-viewed-products')
@endif

@endsection
