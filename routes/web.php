<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestingController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ThreeQuotationController;
use App\Http\Controllers\ThreeMaterialsController;
use App\Http\Controllers\ThreeSceneController;
use App\Http\Controllers\Auth\ClientAuthController;
use App\Models\ThreeScene;
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
     return view('home');
});

// Cliente: login/registro (se usa el nombre de ruta "login" para que el middleware auth redirija correctamente)
Route::get('/login', [ClientAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [ClientAuthController::class, 'login'])->name('client.login');
Route::get('/register', [ClientAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [ClientAuthController::class, 'register'])->name('client.register');
Route::post('/logout', [ClientAuthController::class, 'logout'])->name('logout');
Route::group(['middleware' => ['web', 'auth']], function () use ($disableViteHotIfNotReachable) {
     // Menú de escenarios 3D (por usuario)
     Route::get('/3d', function() use ($disableViteHotIfNotReachable) {
          $disableViteHotIfNotReachable();
          $user = Auth::user();
          $scenes = collect();
          if ($user) {
               $scenes = ThreeScene::query()
                    ->where('user_id', $user->id)
                    ->orderByDesc('updated_at')
                    ->get(['id', 'name', 'updated_at']);
          }

          return view('three.menu', [
               'scenes' => $scenes,
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

     Route::get('/3d/materials', [ThreeMaterialsController::class, 'index'])
          ->name('three.materials');
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


