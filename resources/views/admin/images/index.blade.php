@extends('layouts.admin')
@section('title','Library Images')
@section('css')
    <link rel="stylesheet" href="{{asset('css/admin/library-images.css')}}">
    <link rel="stylesheet" href="{{asset('css/admin/typeroom.css')}}">
@endsection
@section('header')
    <div class="container">
        <div class="title-header">
            <h3 class="text-center">Images</h3>
        </div>
    </div>

@endsection
@section('content')
    <div class="container">
        <div class="row" style="margin-bottom: 50px">
            <form method="post" class="col-md-12" enctype="multipart/form-data">
                @csrf
                <div class="row col-md-12">
                    <div class="col-md-6 library-image">
                        <input type="file" name="image" id="image" accept="image/*" id="imgInp">
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-sm btn-outline-primary button-library">Save</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="row col-md-12 list-images">
            @if($images)
                @foreach($images as $image)
                    <div class="col-md-2 img-show">
                        <img src="{{asset('images/admin/library-images')}}/{{$image->url}}" alt="{{$image->url}}"
                             class="img-thumbnail"/>
                        <form method="post" action="{{route('admin.library-images.delete', $image->id)}}">
                            @csrf
                            <button type="submit" class="btn btn-sm"><span>x</span></button>
                        </form>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
    <script type="text/javascript">
        var loadFile = function (event) {
            var output = document.getElementById('output');
            var att = document.createAttribute("class");
            att.value = "img-thumbnail";
            output.setAttributeNode(att);
            output.src = URL.createObjectURL(event.target.files[0]);
        };
    </script>
@endsection




