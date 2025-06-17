<?php

namespace App\Console\Commands;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddTestPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:add-test 
                            {--user= : User ID to add payments for}
                            {--type=deposit : Payment type (deposit or subscription)}
                            {--count=20 : Number of payments to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add test payment records to user payment history';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->option('user');
        $type = $this->option('type');
        $count = (int) $this->option('count');

        if (! $userId) {
            $this->error('User ID is required. Use --user=1');

            return 1;
        }

        // Validate user exists
        $user = User::find($userId);
        if (! $user) {
            $this->error("User with ID {$userId} not found");

            return 1;
        }

        // Validate and convert payment type
        $paymentType = match ($type) {
            'deposit' => PaymentType::DEPOSIT,
            'subscription' => PaymentType::DIRECT_SUBSCRIPTION,
            default => null,
        };

        if (! $paymentType) {
            $this->error('Invalid payment type. Use "deposit" or "subscription"');

            return 1;
        }

        // For subscription payments, ensure we have subscriptions available
        if ($paymentType === PaymentType::DIRECT_SUBSCRIPTION) {
            $subscriptionCount = Subscription::where('status', 'active')->where('amount', '>', 0)->count();
            if ($subscriptionCount === 0) {
                $this->info('No active subscriptions found. Running subscription seeder...');
                $this->call('db:seed', ['--class' => 'SubscriptionSeeder']);
            }
        }

        $this->info("Adding {$count} test {$type} payments for user {$userId}...");

        DB::transaction(function () use ($user, $paymentType, $count) {
            for ($i = 0; $i < $count; $i++) {
                $this->createTestPayment($user, $paymentType);
            }
        });

        $this->info("Successfully created {$count} test payments!");

        return 0;
    }

    /**
     * Create a single test payment
     */
    private function createTestPayment(User $user, PaymentType $paymentType): void
    {
        // Generate random amount between 10 and 500
        $amount = fake()->randomFloat(2, 10, 500);

        // Choose payment method based on type
        $paymentMethod = $paymentType === PaymentType::DEPOSIT
            ? fake()->randomElement([PaymentMethod::USDT, PaymentMethod::PAY2_HOUSE])
            : fake()->randomElement(PaymentMethod::cases());

        // Random status with weighted distribution (more success than failures)
        $status = fake()->randomElement([
            PaymentStatus::SUCCESS,
            PaymentStatus::SUCCESS,
            PaymentStatus::SUCCESS,
            PaymentStatus::SUCCESS,
            PaymentStatus::PENDING,
            PaymentStatus::FAILED,
        ]);

        // For subscription payments, select random active subscription
        $subscriptionId = null;
        if ($paymentType === PaymentType::DIRECT_SUBSCRIPTION) {
            $subscription = Subscription::where('status', 'active')->inRandomOrder()->first();
            $subscriptionId = $subscription ? $subscription->id : null;

            if (! $subscriptionId) {
                throw new \Exception('No active subscriptions available for DIRECT_SUBSCRIPTION payment');
            }
        }

        Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_type' => $paymentType,
            'subscription_id' => $subscriptionId,
            'payment_method' => $paymentMethod,
            'status' => $status,
            'transaction_number' => 'TEST_'.fake()->uuid(),
        ]);
    }
}
