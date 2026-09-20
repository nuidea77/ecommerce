<!DOCTYPE html>
<html lang="mn" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('shop.name') }} — Гоо сайхны салоны тоног төхөөрөмж, хэрэгсэл</title>
    <meta name="description" content="Гоо сайхны салон, үсчин, гоо сайхны мэргэжилтнүүдэд зориулсан тоног төхөөрөмж, хэрэгслийн онлайн дэлгүүр. QPay төлбөр, хүргэлт.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="/favicon.png" type="image/png">
    <meta name="theme-color" content="#184d3b">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-white text-stone-900" style="background-color:#ffffff">
    <div id="app"></div>
</body>
</html>
