<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ nexus_trans('lucky-wheel.title') }}</title>
</head>
<body>
@include('lucky-wheel::index-content')
</body>
</html>

