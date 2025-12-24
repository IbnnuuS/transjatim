<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\Jobdesk;
use App\Models\JobdeskTask;
use Illuminate\Support\Facades\DB;

// Get the most recent Job desk
$jobdesk = Jobdesk::latest()->first();

if (!$jobdesk) {
    echo "No jobdesks found.\n";
    exit;
}

echo "Latest Jobdesk ID: " . $jobdesk->id . "\n";
echo "Created At: " . $jobdesk->created_at . "\n";
echo "Is Recurring: " . ($jobdesk->is_recurring ? 'Yes' : 'No') . "\n";

// List all tasks for this jobdesk (Raw DB lookup to bypass model scopes/relations temporarily)
$tasks = DB::table('jobdesk_tasks')->where('jobdesk_id', $jobdesk->id)->get();

echo "\nRaw Tasks in DB for this Jobdesk:\n";
foreach ($tasks as $t) {
    echo "Task ID: {$t->id} | Title: {$t->judul} | is_template: (" . gettype($t->is_template) . ") " . var_export($t->is_template, true) . " | Status: {$t->status}\n";
}

// Now test the Eloquent Loading with Filter as done in Controller
$jobdeskEloquent = Jobdesk::with([
    'tasks' => fn($q) => $q->where('is_template', '!=', 1)
])->find($jobdesk->id);

echo "\nEloquent Loaded Tasks (Filtered != 1):\n";
foreach ($jobdeskEloquent->tasks as $t) {
    echo "Task ID: {$t->id} | Title: {$t->judul} | is_template: " . var_export($t->is_template, true) . "\n";
}
