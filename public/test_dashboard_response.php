<?php

use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$user = User::where('email', 'test@example.com')->firstOrFail();
auth()->login($user);

$request = Request::create('/dashboard', 'GET');
$response = $kernel->handle($request);

if ($response instanceof RedirectResponse) {
    echo 'Redirected to: '.$response->getTargetUrl()."\n";
    exit(1);
}

$content = $response->getContent();

if (str_contains($content, 'data-page')) {
    preg_match('/data-page="([^"]+)"/', $content, $matches);
    if (isset($matches[1])) {
        $page = json_decode(html_entity_decode($matches[1]), true);
        $props = $page['props'] ?? [];
        $transactions = $props['transactions']['data'] ?? [];

        echo 'Total Transactions: '.count($transactions)."\n";

        $types = [];
        $sample = [];
        foreach ($transactions as $tx) {
            $name = $tx['type']['name'] ?? 'Unknown';
            $types[$name] = ($types[$name] ?? 0) + 1;
            if (! isset($sample[$name])) {
                $sample[$name] = [
                    'id' => $tx['id'],
                    'amount' => $tx['amount'],
                    'date' => $tx['date'],
                    'raw_date' => $tx['raw_date'],
                    'type' => $name,
                    'category' => $tx['category']['category_name'] ?? null,
                ];
            }
        }

        print_r($types);
        print_r($sample);
    } else {
        echo "Could not find data-page attribute.\n";
    }
} else {
    echo "Not an Inertia response.\n";
}
