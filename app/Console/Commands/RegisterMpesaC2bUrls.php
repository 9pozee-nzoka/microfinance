<?php

namespace App\Console\Commands;

use App\Services\MpesaService;
use Illuminate\Console\Command;

class RegisterMpesaC2bUrls extends Command
{
    protected $signature   = 'mpesa:register-c2b-urls';
    protected $description = 'Register C2B validation/confirmation URLs with Safaricom Daraja API';

    public function handle(MpesaService $mpesa): int
    {
        $this->info('Registering C2B URLs with Safaricom...');
        $this->line('  Confirmation URL : ' . config('services.mpesa.c2b_confirmation_url', route('mpesa.c2b.confirmation')));
        $this->line('  Validation URL   : ' . config('services.mpesa.c2b_validation_url', route('mpesa.c2b.validation')));
        $this->line('  Shortcode        : ' . config('services.mpesa.shortcode'));
        $this->line('  Environment      : ' . config('services.mpesa.env'));
        $this->newLine();

        $result = $mpesa->registerC2bUrls();

        if ($result['success']) {
            $this->info('✓ C2B URLs registered successfully!');
            $this->line('  Response: ' . ($result['message'] ?? 'OK'));
            return Command::SUCCESS;
        }

        $this->error('✗ Registration failed: ' . ($result['message'] ?? 'Unknown error'));
        if (!empty($result['data'])) {
            $this->line('  Details: ' . json_encode($result['data']));
        }
        return Command::FAILURE;
    }
}
