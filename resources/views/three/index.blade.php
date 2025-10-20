<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo 3D</title>
    @vite(['resources/js/three/app.jsx'])
    <style>
      html,body,#r3f-root{height:100%;margin:0}
      #ui{position:fixed;top:12px;left:12px;background:rgba(0,0,0,.6);color:#fff;padding:10px 12px;border-radius:8px;font-family:system-ui,Arial,sans-serif}
      #ui button{margin-right:8px}
    </style>
</head>
<body>
<div id="ui">
  <button id="mat-wood">Piso madera</button>
  <button id="mat-ceramic">Piso cerámica</button>
</div>
<div id="r3f-root"></div>
</body>
</html>
