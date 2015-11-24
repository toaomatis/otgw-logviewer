@extends('baselayout')

@section('content')

    <div class="row">
        <h1>Upload</h1>
        <p>This page will allow you to upload your otdata logfiles to the server, so it can convert it to a database for faster chart creation.</p>
        <hr/>
        {!! Form::open(['route' => 'logfiles']) !!}
        <div class="form-group">
            {!! Form::label('file', 'otdata logfile:') !!}
            {!! Form::file('file', null, ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
            {!! Form::submit('Upload', ['class' => 'btn btn-primary form-control']) !!}
        </div>
        {!! Form::close() !!}
    </div>

@stop