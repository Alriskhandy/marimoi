@extends('errors.layout')

@php
    $code = 503;
    $icon = 'tool';
    $accent = 'slate';
    $title = 'Sedang Dalam Pemeliharaan';
    $message = 'Layanan sementara tidak tersedia.';
    $description = 'Kami sedang melakukan pemeliharaan. Silakan coba lagi nanti.';
    $canReload = true;
@endphp
