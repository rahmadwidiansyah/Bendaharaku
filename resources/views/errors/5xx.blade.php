@extends('errors.layout')

@section('title', 'Kesalahan server')
@section('code', $exception->getStatusCode())
@section('icon', 'server')
@section('heading', 'Terjadi kesalahan pada server')
@section('message', 'Kami sedang mengalami gangguan. Silakan coba lagi beberapa saat lagi.')
