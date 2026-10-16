@extends('errors.layout')

@php
    $code = 403;
    $icon = 'lock';
    $accent = 'red';
    $title = 'Akses Ditolak';
    $message = 'Kamu tidak punya izin untuk membuka halaman ini.';
    $description = 'Hubungi administrator jika kamu merasa seharusnya memiliki akses.';
    $canReload = false;
@endphp
