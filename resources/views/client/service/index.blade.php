@extends('layouts.client')
@section('title','Services')
@section('slidebar')
    <section class="home-slider owl-carousel">
        @if($slidebars->count() > 0)
            @foreach($slidebars as $slidebar)
                <div class="slider-item" style="background-image: url('images/slidebars/{{$slidebar->url}}');">
                    <div class="overlay"></div>
                    <div class="container">
                        <div class="row slider-text align-items-end">
                            <div class="col-md-10 col-sm-12 ftco-animate mb-4">
                                <p class="breadcrumbs"><span class="mr-2"><a href="{{route('client.index')}}">Home</a></span> <span>Services</span></p>
                                <h1 class="mb-3">Services</h1>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </section>
@endsection
@section('content')
    <section class="ftco-section bg-light" id="services">
        <div class="container">
            <div class="row no-gutters">
                @foreach($services as $key =>$service)
                    @if($key%2 == 0)
                        <div class="amenities d-md-flex ftco-animate" style="margin: 30px">
                            <div class="one-half order-first img" id="service">
                                @if($service->icon !== ''  && $service->icon !== null)
                                    <img src="{{asset('images/services')}}/{{$service->icon}}">@else
                                    <img src="{{asset('images/admin/library-images')}}/{{$service->images->first()['url']}}" style="width: 100%; max-height: 300px">
                                @endif
                            </div>
                            <div class="one-half order-last text">
                                <h2 class="text-danger">{{$service->name}}</h2>
                                <p>{!! $service->description !!}</p>
                                <p><a href="{{route('client.services.detail',$service->id)}}" class="btn btn-primary">View</a></p>
                            </div>
                        </div>
                    @else
                        <div class="amenities d-md-flex ftco-animate" style="margin: 30px">
                            <div class="one-half order-last img" id="service">
                                @if($service->icon !== ''  && $service->icon !== null)
                                    <img src="{{asset('images/services')}}/{{$service->icon}}">@else
                                    <img src="{{asset('images/admin/library-images')}}/{{$service->images->first()['url']}}" style="width: 100%; max-height: 300px">
                                @endif
                            </div>
                            <div class="one-half order-first text">
                                <h2  @if($key%2 != 0) class="mb-4 text-danger" @endif>{{$service->name}}</h2>
                                <p>{!! $service->description !!}</p>
                                <p><a href="{{route('client.services.detail',$service->id)}}" class="btn btn-primary">View</a></p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endsection
@push('scripts')
    <script>
        $(document).on("ready",function () {
            $('html, body').animate({
                scrollTop: $("#services").offset().top
            }, 1000);
        });
    </script>
@endpush
