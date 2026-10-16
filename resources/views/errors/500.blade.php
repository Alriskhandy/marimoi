@extends('errors.layout')

@php
    $code = 500;
    $icon = 'server';
    $accent = 'red';
    $title = 'Kesalahan Server';
    $message = 'Terjadi kesalahan di server kami.';
    $description = 'Silakan coba lagi beberapa saat lagi.';
    $canReload = true;
@endphp
