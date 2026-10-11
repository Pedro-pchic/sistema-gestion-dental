@extends('layouts.admin')

@section('title', 'Editar pedido')
@section('heading', 'Editar '.$order->number)
@section('subheading', 'Solo los pedidos pendientes se pueden editar')

@section('content')
    @include('orders._form')
@endsection
