@extends('errors.layout')

@section('title', 'Permintaan tidak dapat diproses')
@section('code', $exception->getStatusCode())
@section('icon', 'alert')
@section('heading', 'Permintaan tidak dapat diproses')
@section('message', 'Terjadi kesalahan saat memproses permintaan Anda. Periksa kembali lalu coba lagi.')
