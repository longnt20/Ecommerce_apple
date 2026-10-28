<?php

require dirname(__DIR__).'/bootstrap.php';

if (Tests\Support\TestingEnvironment::database()['mode'] !== 'disabled') {
    throw new RuntimeException('Generate the route inventory in database-disabled mode.');
}

$app = Tests\Support\TestApplication::create();
$lines = [
    '# P0 route baseline', '',
    'Generated from the Laravel route collection in database-disabled testing mode. No endpoint is called.', '',
    'A public method existing does not prove the endpoint works end-to-end. Closure bodies and provider integrations are not executed.', '',
    '| Methods | URI | Action | Declared middleware | Handler |',
    '| --- | --- | --- | --- | --- |',
];
$missing = [];
$count = 0;
foreach ($app['router']->getRoutes() as $route) {
    $action = $route->getActionName();
    $status = 'Closure';
    if ($action !== 'Closure') {
        [$class, $method] = explode('@', $action) + [1 => '__invoke'];
        $exists = class_exists($class) && method_exists($class, $method)
            && (new ReflectionMethod($class, $method))->isPublic();
        $status = $exists ? 'Method exists' : 'MISSING';
        if (! $exists) {
            $missing[] = implode('/', $route->methods()).' '.$route->uri().' -> '.$action;
        }
    }
    $lines[] = '| '.implode(', ', $route->methods()).' | `'.$route->uri().'` | `'.$action.'` | `'.implode(', ', $route->middleware()).'` | '.$status.' |';
    $count++;
}
$lines[] = '';
$lines[] = 'Total route entries: '.$count.'. Missing controller methods: '.count($missing).'.';
$lines[] = '';
$lines[] = '## Missing handlers';
$lines[] = '';
foreach ($missing as $item) {
    $lines[] = '- `'.$item.'`';
}
$path = dirname(__DIR__, 2).'/docs/architecture/p0-route-baseline.md';
file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);
echo 'Wrote docs/architecture/p0-route-baseline.md; routes='.$count.'; missing='.count($missing).PHP_EOL;
