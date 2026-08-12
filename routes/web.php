<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestingController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ThreeQuotationController;
use App\Http\Controllers\ThreeMaterialsController;
use App\Http\Controllers\ThreeInventoryController;
use App\Http\Controllers\ThreeSceneController;
use App\Http\Controllers\ThreeQuoteController;
use App\Http\Controllers\ThreeInteractionEventController;
use App\Http\Controllers\ThreeRecommendationController;
use App\Http\Controllers\Auth\ClientAuthController;
use App\Models\ThreeScene;
use App\Models\ThreeQuote;
use App\Models\Producto;
use Illuminate\Support\Facades\Auth;

$disableViteHotIfNotReachable = function (): void {
      $hotFile = public_path('hot');
      if (!is_file($hotFile)) {
           return;
      }

      $url = trim((string) file_get_contents($hotFile));
      if ($url === '') {
           @unlink($hotFile);
           return;
      }

      $parts = parse_url($url);
      $host = $parts['host'] ?? null;
      $port = (int) ($parts['port'] ?? 5173);
      if (!$host || $port <= 0) {
           @unlink($hotFile);
           return;
      }

      $fp = @fsockopen($host, $port, $errno, $errstr, 0.2);
      if (is_resource($fp)) {
           fclose($fp);
           return;
      }

      @unlink($hotFile);
};

// Root redirect to Orchid admin prefix (e.g., /admin)
Route::get('/', function () {
     $homeProducts = Producto::query()
          ->with(['categoria', 'attachment'])
          ->orderByDesc('id')
          ->get()
          ->map(function (Producto $p): array {
               $image = $p->attachment('image')->first();

               $imageUrl = null;
               $hasImage = false;
               if ($image) {
                    if (is_object($image) && method_exists($image, 'url')) {
                         $imageUrl = $image->url();
                    }
                    if (empty($imageUrl)) {
                         $path = $image->path ?? null;
                         if ($path) {
                              $base = config('filesystems.disks.public.url', url('/storage'));
                              $imageUrl = rtrim($base, '/') . '/' . ltrim($path, '/');
                         }
                    }
                    $hasImage = !empty($imageUrl);
               }

               if (empty($imageUrl)) {
                    $svg = rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360"><rect fill="#f3f4f6" width="100%" height="100%"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#9ca3af" font-size="28" font-family="Arial, Helvetica, sans-serif">Sin imagen</text></svg>');
                    $imageUrl = 'data:image/svg+xml;utf8,' . $svg;
               }

               return [
                    'id' => $p->id,
                    'name' => (string) ($p->Nombre ?? ('Producto #' . $p->id)),
                    'category' => (string) (optional($p->categoria)->Nombre ?? ''),
                    'price' => $p->Precio,
                    'image' => $imageUrl,
                    'has_image' => $hasImage,
               ];
          });

     return view('home', [
          'homeProducts' => $homeProducts,
     ]);
});

// Cliente: login/registro (se usa el nombre de ruta "login" para que el middleware auth redirija correctamente)
Route::middleware('guest')->group(function () {
    Route::get('/login', [ClientAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [ClientAuthController::class, 'login'])->name('client.login');
    Route::get('/register', [ClientAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [ClientAuthController::class, 'register'])->name('client.register');
});
Route::post('/logout', [ClientAuthController::class, 'logout'])->name('logout');
Route::group(['middleware' => ['web', 'auth']], function () use ($disableViteHotIfNotReachable) {
     // Menú de escenarios 3D (por usuario)
     Route::get('/3d', function() use ($disableViteHotIfNotReachable) {
          $disableViteHotIfNotReachable();
          $user = Auth::user();
          $scenes = collect();
          $quotesByScene = collect();
          if ($user) {
               $scenes = ThreeScene::query()
                    ->where('user_id', $user->id)
                    ->orderByDesc('updated_at')
                    ->get(['id', 'name', 'updated_at']);

                $sceneIds = $scenes->pluck('id')->all();
                if (!empty($sceneIds)) {
                    $quotesByScene = ThreeQuote::query()
                        ->where('user_id', $user->id)
                        ->whereIn('three_scene_id', $sceneIds)
                        ->orderByDesc('created_at')
                        ->get()
                        ->groupBy('three_scene_id')
                        ->map(fn ($group) => $group->first());
                }
          }

          return view('three.menu', [
               'scenes' => $scenes,
               'quotesByScene' => $quotesByScene,
               'user' => $user,
          ]);
     })->name('three.demo');

     // Editor 3D
     Route::get('/3d/editor', function() use ($disableViteHotIfNotReachable) {
          $disableViteHotIfNotReachable();
          return view('three.index');
     })->name('three.editor');

     // Demo 3D Room avanzada
     Route::get('/3d/room', function() use ($disableViteHotIfNotReachable) {
          $disableViteHotIfNotReachable();
          return view('three.room');
     })->name('three.room');

     Route::post('/3d/quotation', [ThreeQuotationController::class, 'generate'])
          ->name('three.quotation');

     Route::get('/3d/quotes/{quoteId}/download', [ThreeQuoteController::class, 'download'])
          ->whereNumber('quoteId')
          ->name('three.quotes.download');

     Route::post('/3d/quotes/{quoteId}/send', [ThreeQuoteController::class, 'send'])
          ->whereNumber('quoteId')
          ->name('three.quotes.send');

     Route::get('/3d/materials', [ThreeMaterialsController::class, 'index'])
          ->name('three.materials');

     Route::get('/3d/inventory', [ThreeInventoryController::class, 'snapshot'])
          ->name('three.inventory');

     Route::post('/3d/events', [ThreeInteractionEventController::class, 'store'])
          ->name('three.events.store');

     Route::get('/3d/recommendations', [ThreeRecommendationController::class, 'index'])
          ->name('three.recommendations');
});

// API Chatbot (OpenAI) - excluir CSRF explícitamente
Route::post('/api/chatbot', [ChatbotController::class, 'handle'])
     ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
     ->name('api.chatbot');

Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('/3d/scenes', [ThreeSceneController::class, 'index'])
         ->name('three.scenes.index');
    Route::post('/3d/scenes', [ThreeSceneController::class, 'store'])
         ->name('three.scenes.store');
    Route::get('/3d/scenes/{sceneId}', [ThreeSceneController::class, 'show'])
         ->whereNumber('sceneId')
         ->name('three.scenes.show');
    Route::put('/3d/scenes/{sceneId}', [ThreeSceneController::class, 'update'])
         ->whereNumber('sceneId')
         ->name('three.scenes.update');

    Route::get('testing/smoke', [TestingController::class, 'smoke'])
         ->name('testing.smoke');

    Route::get('testing/testcases', [TestingController::class, 'testCases'])
         ->name('testing.testcases');

    Route::get('testing/integration', [TestingController::class, 'integration'])
         ->name('testing.integration');

    Route::get('testing/locust', [TestingController::class, 'locust'])
         ->name('testing.locust');

     Route::get('testing/status/{uuid}', [TestingController::class, 'status'])
     ->name('testing.status');

     Route::get('testing/download/{uuid}', [TestingController::class, 'downloadReport'])
     ->name('testing.download');
});


