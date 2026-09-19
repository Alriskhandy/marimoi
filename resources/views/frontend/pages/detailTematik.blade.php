@extends('frontend.layouts.spatial', ['title' => 'Detail Kegiatan - MARIMOI', 'heroTitle' => 'Detail Peta'])

@section('subtitle', $project->kategori->nama ?? 'Informasi lokasi dan atribut data.')

@section('main')
    @include('frontend.partials.detail-peta')
@endsection
