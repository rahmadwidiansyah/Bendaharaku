@extends('errors.layout')

@section('title', 'Akses dibatasi')
@section('code', '403')
@section('icon', 'shield')
@section('heading', 'Akses dibatasi')
@section('message', ($exception ?? null)?->getMessage() ?: 'Anda tidak memiliki izin untuk membuka halaman ini.')
