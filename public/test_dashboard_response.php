<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'test@example.com')->firstOrFail();
auth()->login($user);

$request = Illuminate\Http\Request::create('/dashboard', 'GET');
$response = $kernel->handle($request);

if ($response instanceof \Illuminate\Http\RedirectResponse) {
    echo "Redirected to: " . $response->getTargetUrl() . "\n";
    exit(1);
}

$content = $response->getContent();

if (str_contains($content, 'data-page')) {
    preg_match('/data-page="([^"]+)"/', $content, $matches);
    if (isset($matches[1])) {
        $page = json_decode(html_entity_decode($matches[1]), true);
        $props = $page['props'] ?? [];
        $transactions = $props['transactions']['data'] ?? [];
        
        echo "Total Transactions: " . count($transactions) . "\n";
        
        $types = [];
        $sample = [];
        foreach ($transactions as $tx) {
            $name = $tx['type']['name'] ?? 'Unknown';
            $types[$name] = ($types[$name] ?? 0) + 1;
            if (!isset($sample[$name])) {
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
