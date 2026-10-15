@extends('emails.layout-branded')

@section('content')
    {!! \App\Support\SafeEmailHtml::clean($body ?? '') !!}
@endsection
