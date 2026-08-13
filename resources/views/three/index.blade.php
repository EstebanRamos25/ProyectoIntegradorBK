<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Experiencia 3D - CERABOL</title>
    @vite(['resources/js/three/app.jsx'])
    <style>
      html,body,#r3f-root{height:100%;margin:0;background:#0f172a;overflow:hidden}
    </style>
</head>
<body>
<div id="r3f-root"></div>
</body>
</html>
