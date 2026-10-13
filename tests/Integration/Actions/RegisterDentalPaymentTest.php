<?php

namespace Tests\Integration\Actions;

use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class RegisterDentalPaymentTest extends TestCase
{
    public function test_concurrent_payments_cannot_overpay_the_same_invoice(): void
    {
        $connection = config('database.connections.pgsql');
        $database = (string) ($connection['database'] ?? '');

        if (
            DB::connection()->getDriverName() !== 'pgsql'
            || ! app()->environment('testing')
            || getenv('DENTAL_CONCURRENCY_TESTS') !== '1'
            || ! str_ends_with($database, '_test')
            || ! in_array($connection['host'] ?? null, ['127.0.0.1', 'localhost', '::1'], true)
            || filled($connection['url'] ?? null)
        ) {
            $this->markTestSkipped('Requires explicit opt-in and a local PostgreSQL test database ending in _test.');
        }

        $role = Role::factory()->create();
        $actor = User::factory()->for($role)->create(['is_active' => true]);
        $patient = Patient::factory()->create();
        $invoice = DentalInvoice::factory()
            ->for($patient)
            ->for($actor, 'creator')
            ->issued()
            ->create([
                'subtotal' => '100.00',
                'total' => '100.00',
                'balance_due' => '100.00',
            ]);

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dental-payment-concurrency-'.Str::uuid();
        mkdir($directory);

        $barrierPath = $directory.DIRECTORY_SEPARATOR.'start';
        $resultPaths = [];
        $processes = [];
        $databaseConnection = DB::connection();

        try {
            $databaseConnection->beginTransaction();
            DentalInvoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            foreach ([1, 2] as $worker) {
                $readyPath = $directory.DIRECTORY_SEPARATOR.'ready-'.$worker;
                $resultPath = $directory.DIRECTORY_SEPARATOR.'result-'.$worker;
                $resultPaths[] = $resultPath;
                $process = new Process(
                    [PHP_BINARY, '-r', $this->workerCode()],
                    base_path(),
                    $this->workerEnvironment($connection, $invoice, $actor, $worker, $readyPath, $barrierPath, $resultPath),
                    timeout: 30,
                );
                $process->start();
                $processes[] = $process;
            }

            $this->waitForWorkers($processes, [
                $directory.DIRECTORY_SEPARATOR.'ready-1',
                $directory.DIRECTORY_SEPARATOR.'ready-2',
            ]);

            file_put_contents($barrierPath, 'go');
            $this->waitForWorkersToBlockOnInvoice();
            $databaseConnection->commit();

            $outcomes = [];

            foreach ($processes as $index => $process) {
                $process->wait();
                $workerError = $process->getErrorOutput();

                if (! $process->isSuccessful() && is_file($resultPaths[$index])) {
                    $workerError .= file_get_contents($resultPaths[$index]);
                }

                $this->assertTrue($process->isSuccessful(), $workerError);
                $outcomes[] = json_decode(file_get_contents($resultPaths[$index]), true, flags: JSON_THROW_ON_ERROR)['status'];
            }

            $this->assertSame(1, count(array_filter($outcomes, fn (string $status): bool => $status === 'registered')));
            $this->assertSame(1, count(array_filter($outcomes, fn (string $status): bool => $status === 'rejected')));
            $this->assertSame('40.00', $invoice->fresh()->balance_due);
            $this->assertSame(1, DentalPayment::query()->where('dental_invoice_id', $invoice->getKey())->count());
        } finally {
            if ($databaseConnection->transactionLevel() > 0) {
                $databaseConnection->rollBack();
            }

            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }

            DB::table('dental_payments')->where('dental_invoice_id', $invoice->getKey())->delete();
            DB::table('dental_invoices')->where('id', $invoice->getKey())->delete();
            DB::table('patients')->where('id', $patient->getKey())->delete();
            DB::table('users')->where('id', $actor->getKey())->delete();
            DB::table('roles')->where('id', $role->getKey())->delete();

            foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                unlink($file);
            }

            rmdir($directory);
        }
    }

    /** @param list<Process> $processes
     * @param  list<string>  $readyPaths
     */
    private function waitForWorkers(array $processes, array $readyPaths): void
    {
        $deadline = microtime(true) + 15;

        while (count(array_filter($readyPaths, 'is_file')) < count($readyPaths)) {
            foreach ($processes as $process) {
                if (! $process->isRunning()) {
                    $this->fail('A payment worker exited before reaching the barrier. '.$process->getErrorOutput());
                }
            }

            if (microtime(true) >= $deadline) {
                $this->fail('Payment workers did not reach the concurrency barrier in time.');
            }

            usleep(10_000);
        }
    }

    private function waitForWorkersToBlockOnInvoice(): void
    {
        $deadline = microtime(true) + 15;

        do {
            $waitingWorkers = (int) DB::selectOne(
                'SELECT COUNT(*) AS aggregate FROM pg_stat_activity WHERE application_name IN (?, ?) AND wait_event_type = ?',
                ['dental-payment-test-1', 'dental-payment-test-2', 'Lock'],
            )->aggregate;

            if ($waitingWorkers === 2) {
                return;
            }

            if (microtime(true) >= $deadline) {
                $this->fail('Both payment workers did not contend for the invoice lock.');
            }

            usleep(10_000);
        } while (true);
    }

    /** @return array<string, string> */
    private function workerEnvironment(
        array $connection,
        DentalInvoice $invoice,
        User $actor,
        int $worker,
        string $readyPath,
        string $barrierPath,
        string $resultPath,
    ): array {
        return [
            'APP_ENV' => 'testing',
            'APP_KEY' => (string) config('app.key'),
            'DB_CONNECTION' => 'pgsql',
            'DB_URL' => '',
            'DB_HOST' => (string) $connection['host'],
            'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => (string) $connection['database'],
            'DB_USERNAME' => (string) $connection['username'],
            'DB_PASSWORD' => (string) $connection['password'],
            'DB_SSLMODE' => (string) ($connection['sslmode'] ?? 'prefer'),
            'PGAPPNAME' => 'dental-payment-test-'.$worker,
            'DENTAL_WORKER_INVOICE_ID' => (string) $invoice->getKey(),
            'DENTAL_WORKER_ACTOR_ID' => (string) $actor->getKey(),
            'DENTAL_WORKER_READY' => $readyPath,
            'DENTAL_WORKER_BARRIER' => $barrierPath,
            'DENTAL_WORKER_RESULT' => $resultPath,
            'DENTAL_WORKER_IDEMPOTENCY_KEY' => (string) Str::uuid(),
        ];
    }

    private function workerCode(): string
    {
        return <<<'PHP'
            $basePath = getcwd();
            require $basePath.'/vendor/autoload.php';
            $app = require $basePath.'/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

            file_put_contents(getenv('DENTAL_WORKER_READY'), 'ready');
            $deadline = microtime(true) + 15;

            while (! is_file(getenv('DENTAL_WORKER_BARRIER'))) {
                if (microtime(true) >= $deadline) {
                    throw new RuntimeException('Concurrency barrier timed out.');
                }

                usleep(10_000);
            }

            try {
                $invoice = App\Models\DentalInvoice::query()->findOrFail((int) getenv('DENTAL_WORKER_INVOICE_ID'));
                $actor = App\Models\User::query()->findOrFail((int) getenv('DENTAL_WORKER_ACTOR_ID'));

                app(App\Actions\RegisterDentalPayment::class)->handle($invoice, [
                    'amount' => '60.00',
                    'method' => 'cash',
                    'payment_date' => today()->toDateString(),
                    'idempotency_key' => getenv('DENTAL_WORKER_IDEMPOTENCY_KEY'),
                ], $actor);

                $status = 'registered';
            } catch (Illuminate\Validation\ValidationException $exception) {
                $status = 'rejected';
            } catch (Throwable $exception) {
                file_put_contents(getenv('DENTAL_WORKER_RESULT'), json_encode([
                    'status' => 'error',
                    'error_type' => get_class($exception),
                ], JSON_THROW_ON_ERROR));
                exit(1);
            }

            file_put_contents(getenv('DENTAL_WORKER_RESULT'), json_encode(['status' => $status], JSON_THROW_ON_ERROR));
            PHP;
    }
}
