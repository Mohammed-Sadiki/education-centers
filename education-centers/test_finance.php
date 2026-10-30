<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'admin.tanger@education-centers.ma')->first();
echo "Testing as Center Admin: " . $user->email . " (center_id=" . $user->center_id . ")" . PHP_EOL;

Auth::login($user);

$req = Illuminate\Http\Request::create('/super-admin/finance', 'GET');
$req->setUserResolver(fn() => $user);

try {
    $controller = $app->make(App\Http\Controllers\FinanceDashboardController::class);
    $response = $controller->index($req);
    echo "Controller index() executed successfully! View name: " . $response->name() . PHP_EOL;
} catch (\Throwable $e) {
    echo "ERROR in index(): " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}

