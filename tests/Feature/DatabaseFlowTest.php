<?php

declare(strict_types=1);

use B7s\QueueFlow\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(TestCase::class);

// Helper functions
function hasDatabaseConfigured(): bool
{
    try {
        DB::connection()->getPdo();
        return true;
    } catch (Exception) {
        return false;
    }
}

function setupTestDatabase(): void
{
    if (hasDatabaseConfigured()) {
        return;
    }

    // Fallback to SQLite in-memory if no database is configured
    config(['database.default' => 'parallite_testing']);
    config(['database.connections.parallite_testing' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);}

function createAuditLogsTable(): void
{
    Schema::dropIfExists('queue_audit_logs');
    Schema::create('queue_audit_logs', function (Blueprint $table): void {
        $table->id();
        $table->string('job_name');
        $table->string('status');
        $table->timestamps();
    });
}

// Test case
test('database flow - performs CRUD operations on queue audit records', function (): void {
    // Skip if no database is available
    if (!hasDatabaseConfigured() && !extension_loaded('pdo_sqlite')) {
        test()->markTestSkipped('No database configured and SQLite extension not available for PHP');
        return;
    }

    $originalConfig = config('database.connections');
    
    try {
        setupTestDatabase();
        
        DB::beginTransaction();
        
        createAuditLogsTable();
        
        $jobName = uniqid('daily-report-');
        
        qflow(function () use ($jobName): void {
            DB::table('queue_audit_logs')->insert([
                'job_name' => $jobName,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $record = DB::table('queue_audit_logs')->where('job_name', $jobName)->first();
        expect($record)->not->toBeNull()
            ->and($record->status)->toBe('pending');

        qflow(function () use ($jobName): void {
            DB::table('queue_audit_logs')
                ->where('job_name', $jobName)
                ->update([
                    'status' => 'processing',
                    'updated_at' => now(),
                ]);
        });

        $updated = DB::table('queue_audit_logs')->where('job_name', $jobName)->first();
        expect($updated->status)->toBe('processing');

        qflow(function () use ($jobName): void {
            DB::table('queue_audit_logs')
                ->where('job_name', $jobName)
                ->delete();
        });

        $remaining = DB::table('queue_audit_logs')->where('job_name', $jobName)->count();
        expect($remaining)->toBe(0);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        config(['database.connections' => $originalConfig]);
    }
});
