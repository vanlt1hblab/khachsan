@extends('layouts.admin')
@section('title','Rooms')
@section('css')
    <style>
        .detail-typeRoom{
            position: relative;
        }

        .detail-typeRoom>a{
            position: absolute;
            right: 0;
            top: 0;
        }
    </style>
    <link rel="stylesheet" href="{{asset('css/admin/typeroom.css')}}">
    <link rel="stylesheet" href="{{asset('css/admin/device.css')}}">
@endsection
@section('header')
    <div class="container">
        <div class="title-header">
            <h3 class="text-center">Rooms</h3>
        </div>
    </div>
@endsection
@section('content')
    <div class="container">
        <div class="row col-md-12">
            @foreach($typeRooms as $typeRoom)
                <div class="col-md-12 detail-typeRoom">
                    <h3>{{$typeRoom->name}}</h3>
                    <a href="{{route('admin.type-rooms.rooms.create',$typeRoom->id)}}" class="btn btn-outline-primary btn-sm right"><i class="fa fa-plus-circle"></i></a>
                </div>
                <div class="row col-md-12" style="margin-top: 50px">
                    @foreach($typeRoom->rooms as $room)
                        <div class="col-md-2">
                            <div class="room">
                                <div class="style-rooms @if($room->status_id === 1) bg-status-1
                                                                @elseif($room->status_id === 2) bg-status-2
                                                                @elseif($room->status_id === 3) bg-status-3
                                                                @else bg-status-4 @endif">
                                    <div style="float: right;">
                                        <button class="btn btn-sm btn-outline-warning" data-toggle="modal"
                                                data-target="#room-{{$room->id}}"><i
                                                class="fa fa-info"></i></button>
                                        <a href="{{route('admin.type-rooms.rooms.edit',['id' => $room->typeRoom, 'idRoom' => $room->id])}}" class="btn btn-sm btn-outline-primary"><i
                                                class="fa fa-edit"></i></a>
                                        <a href="#" class="btn btn-sm btn-outline-danger"><i
                                                class="fa fa-trash"></i></a>
                                    </div>
                                    <h4>Room {{$room->name}}</h4>
                                </div>
                            </div>
                        </div>

                        {{--Modal--}}
                        <div class="modal fade" id="room-{{$room->id}}" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
                             aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Detail Room {{$room->name}}</h5>
                                    </div>
                                    <div class="modal-body">
                                        <div class="container-fluid">
                                            @foreach($room->orderDetails as $orderDetail)
                                                @if($orderDetail->start_date >= \Carbon\Carbon::now()->format('Y-m-d'))
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <h5 class="text-danger">{{$orderDetail->start_date}} ~ {{$orderDetail->end_date}}</h5>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <h6 class="text-primary">Type Room : {{$room->typeRoom->name}}</h6>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <ul class="col-md-12">
                                                                <li>Max people : {{$room->typeRoom->people}}</li>
                                                                <li>Price : $ {{$room->typeRoom->price}}</li>
                                                                @if($room->typeRoom->sale > 0)
                                                                    <li>Sale : {{$room->typeRoom->sale}} %</li>
                                                                @endif
                                                                <li>Acreage : {{$room->typeRoom->acreage}} m <sup>2</sup></li>
                                                                <li>Orderer : {{$orderDetail->orderTypeRoom->order->user->name}}</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-sm btn-outline-success" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@endsection
@push('scripts')
    <script>

    </script>

@endpush
