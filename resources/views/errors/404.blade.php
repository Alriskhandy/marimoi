@extends('errors.layout')

@section('title', 'Halaman Tidak Ditemukan')

@php
    $code = 404;
    $message = 'Oops! Halaman yang kamu cari tidak ada.';
@endphp
