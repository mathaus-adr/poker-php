<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet"/>

    <!-- Styles -->
    <style>
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/websocket.js'])
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
<div class="container bg-white p-8 rounded shadow-md w-96 mx-auto">

    <livewire:initial-page/>
</div>
</body>
</html>
