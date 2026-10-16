@extends('errors.layout')

@php
    $code = 404;
    $icon = 'search';
    $accent = 'blue';
    $title = 'Halaman Tidak Ditemukan';
    $message = 'Halaman yang kamu cari tidak ada atau sudah dipindahkan.';
    $description = 'Periksa kembali alamat yang dimasukkan, atau kembali ke beranda.';
    $canReload = false;
@endphp
