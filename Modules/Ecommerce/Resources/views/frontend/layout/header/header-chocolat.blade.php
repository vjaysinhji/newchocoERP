<header class="bg-black text-white" style="background-color: {{ $ecommerce_setting->header_bg_color ?? '#000000' }} !important;">
    {{-- Top bar --}}
        <div class="border-b border-white/20">
        <div class="container mx-auto px-4 py-2 flex justify-end items-center gap-6 text-sm">
            <a href="{{ url('') }}" class="hover:underline">{{ app()->getLocale() == 'ar' ? 'المواقع' : 'LOCATIONS' }}</a>
            <a href="{{ url('contact') }}" class="hover:underline">{{ app()->getLocale() == 'ar' ? 'المساعدة' : 'HELP' }}</a>
            <span class="flex gap-2">
                <a href="{{ route('set.locale', 'en') }}" class="hover:underline {{ app()->getLocale() == 'en' ? 'font-bold underline' : '' }}">EN</a>
                <span>/</span>
                <a href="{{ route('set.locale', 'ar') }}" class="hover:underline {{ app()->getLocale() == 'ar' ? 'font-bold underline' : '' }}">AR</a>
            </span>
        </div>
    </div>
    {{-- Main header --}}
    <div class="container mx-auto px-4 py-4">
        <div class="flex flex-col lg:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4 order-2 lg:order-1 w-full lg:w-auto justify-center">
                <a href="{{ url('/') }}" class="flex-shrink-0">
                    @if(isset($ecommerce_setting->logo))
                    <img src="{{ url('frontend/images/') }}/{{ $ecommerce_setting->logo }}" alt="{{ $ecommerce_setting->site_title ?? '' }}" class="h-10 md:h-12 object-contain">
                    @else
                    <img src="{{ asset('logo') }}/{{ $general_setting->site_logo }}" alt="{{ $ecommerce_setting->site_title ?? '' }}" class="h-10 md:h-12 object-contain brightness-0 invert">
                    @endif
                </a>
                <form action="{{ route('products.search') }}" method="post" class="hidden md:block flex-1 max-w-md">
                    @csrf
                    <div class="relative">
                        <input type="text" name="search" placeholder="{{ app()->getLocale() == 'ar' ? 'ابحث عن منتج' : 'Search for a product' }}" class="w-full bg-white/10 border border-white/30 rounded px-4 py-2 text-white placeholder-white/60 focus:outline-none focus:border-white">
                        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 text-white/80 hover:text-white">
                            <i class="material-symbols-outlined text-xl">search</i>
                        </button>
                    </div>
                    <div class="search_result absolute z-50 mt-1 w-full bg-white text-gray-800 rounded shadow-lg" style="display:none;"></div>
                </form>
            </div>
            <div class="flex items-center gap-4 order-3">
                @guest
                <a href="{{ url('customer/login') }}" class="text-white hover:underline"><i class="material-symbols-outlined align-middle">person</i></a>
                @endguest
                @if(auth()->user() && auth()->user()->role_id == 5)
                <a href="{{ url('customer/account-details') }}" class="text-white hover:underline"><i class="material-symbols-outlined align-middle">person</i></a>
                @endif
                @if(isset($ecommerce_setting->online_order) && $ecommerce_setting->online_order == 1)
                <a href="{{ url('customer/wishlist') }}" class="text-white hover:underline relative">
                    <i class="material-symbols-outlined align-middle">favorite</i>
                    @if(($wishlist_count ?? 0) > 0)
                    <span class="absolute -top-1 -right-1 bg-white text-black text-xs rounded-full w-4 h-4 flex items-center justify-center">{{ $wishlist_count }}</span>
                    @endif
                </a>
                <a href="{{ url('cart') }}" class="text-white hover:underline relative cart__menu inline-flex items-center gap-1">
                    <i class="material-symbols-outlined align-middle">shopping_bag</i>
                    <span class="cart_qty">{{ session()->get('total_qty', 0) }}</span>
                    <span class="total text-sm">
                        @php
                            $subTotal = session()->get('subTotal', 0);
                            $curr = session()->get('currency_code') ? \App\Models\Currency::where('code', session()->get('currency_code'))->first() : \App\Models\Currency::where('is_active', 1)->first();
                        @endphp
                        @if($curr && $general_setting->currency_position == 'prefix')
                        {{ $curr->symbol ?? $curr->code }} {{ number_format($subTotal * ($curr->exchange_rate ?? 1), 2) }}
                        @elseif($curr)
                        {{ number_format($subTotal * ($curr->exchange_rate ?? 1), 2) }} {{ $curr->symbol ?? $curr->code }}
                        @endif
                    </span>
                </a>
                @endif
            </div>
        </div>
        {{-- Mobile search --}}
        <form action="{{ route('products.search') }}" method="post" class="md:hidden mt-3">
            @csrf
            <input type="text" name="search" placeholder="{{ app()->getLocale() == 'ar' ? 'ابحث' : 'Search' }}" class="w-full bg-white/10 border border-white/30 rounded px-4 py-2 text-white placeholder-white/60">
        </form>
    </div>
    {{-- Main Nav --}}
    <nav class="border-t border-white/20">
        <div class="container mx-auto px-4">
            <ul class="flex flex-wrap justify-center gap-6 py-3 text-sm font-semibold uppercase">
                @if(!empty($topNavItems))
                @foreach($topNavItems as $nav)
                @if(empty($nav->children[0]))
                @if($nav->type == 'custom')
                <li><a href="{{ $nav->slug }}" target="_blank" class="hover:underline">{{ $nav->name ?? $nav->title }}</a></li>
                @elseif($nav->type == 'category')
                <li><a href="{{ url('shop/' . $nav->slug) }}" class="hover:underline">{{ $nav->name ?? $nav->title }}</a></li>
                @elseif($nav->type == 'page' && ($nav->slug ?? '') == 'home')
                <li><a href="{{ url('/') }}" class="hover:underline">{{ $nav->name ?? $nav->title }}</a></li>
                @elseif($nav->type == 'collection')
                <li><a href="{{ url('collections/' . $nav->slug) }}" class="hover:underline">{{ $nav->name ?? $nav->title }}</a></li>
                @elseif($nav->type == 'brand')
                <li><a href="{{ url('brands/' . $nav->slug) }}" class="hover:underline">{{ $nav->name ?? $nav->title }}</a></li>
                @else
                <li><a href="{{ url($nav->slug) }}" class="hover:underline">{{ $nav->name ?? $nav->title }}</a></li>
                @endif
                @else
                <li class="relative group">
                    <a href="#" class="hover:underline">{{ $nav->name ?? $nav->title }} <i class="material-symbols-outlined text-sm align-middle">expand_more</i></a>
                    <ul class="absolute left-0 top-full pt-2 hidden group-hover:block bg-black border border-white/20 rounded min-w-[200px] z-50">
                        @foreach($nav->children[0] ?? [] as $child)
                        <li><a href="{{ $child->type == 'custom' ? $child->slug : url($child->slug) }}" class="block px-4 py-2 hover:bg-white/10" {{ $child->type == 'custom' ? 'target="_blank"' : '' }}>{{ $child->name ?? $child->title }}</a></li>
                        @endforeach
                    </ul>
                </li>
                @endif
                @endforeach
                @else
                <li><a href="{{ url('/') }}" class="hover:underline">{{ app()->getLocale() == 'ar' ? 'الرئيسية' : 'HOME' }}</a></li>
                <li><a href="{{ url('shop') }}" class="hover:underline">{{ app()->getLocale() == 'ar' ? 'المتجر' : 'SHOP' }}</a></li>
                @endif
            </ul>
        </div>
    </nav>
</header>
