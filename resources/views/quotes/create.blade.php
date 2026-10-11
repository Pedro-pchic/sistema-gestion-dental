@extends('layouts.admin')

@section('title', 'Nueva cotización')
@section('heading', 'Nueva cotización')
@section('subheading', 'Agrega los productos y precios propuestos')

@section('content')
    @include('quotes._form')
@endsection
