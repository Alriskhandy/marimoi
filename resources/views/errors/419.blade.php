@extends('errors.layout')

@php
    $code = 419;
    $icon = 'clock';
    $accent = 'amber';
    $title = 'Sesi Kedaluwarsa';
    $message = 'Sesi halaman ini telah berakhir.';
    $description = 'Silakan muat ulang halaman lalu coba lagi.';
    $canReload = true;
@endphp
