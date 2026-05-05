@extends('errors.minimal')

@section('title', __('403 Forbidden'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: "The page you are trying to access is restricted. If you believe this is a mistake, please contact the administrator."))
