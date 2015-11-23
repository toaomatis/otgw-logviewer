@extends('baselayout')

@section('content')

    <div class="row">
        <h1>Welcome to my website</h1>
        <p>We are creating something beautiful today.</p>
    </div>

    <div class="dropdown">
        <button id="logfiles-button" class="btn btn-primary dropdown-toggle" disabled="disabled" type="button" data-toggle="dropdown">Select logfile for parsing <span class="caret"></span></button>
        <ul id="logfiles" class="dropdown-menu">
        </ul>
    </div>

    <div id="chart_div"></div>

    <script src="js/otgw.js"></script>
@stop