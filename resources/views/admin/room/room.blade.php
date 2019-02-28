@extends('layouts.admin')
@section('title','Rooms')
@section('header')
    <div class="container">
        <div class="title-header">
            <h3 class="text-center">Room</h3>
        </div>
    </div>
@endsection
@section('content')
    <div class="container">
        <table class="table table-striped table-bordered" id="room-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
        </table>
    </div>
    <div class="container" style="clear: both">
        <a href="{{route('admin.type-rooms.index', $idTypeRoom)}}" class="btn btn-sm btn-outline-success">Back</a>
        <a href="{{route('admin.type-rooms.rooms.create', $idTypeRoom)}}" class="btn btn-sm btn-outline-info"><i
                class="fa fa-plus-circle"></i> Add</a>
    </div>
@endsection
@push('scripts')
    <script>
        $(function () {
            $('#room-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{!!route('admin.type-rooms.rooms.getListRoomByTypeRoom', $idTypeRoom)!!}',
                },
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'status_name', name: 'status_name'},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ]
            });
        });
    </script>
@endpush
