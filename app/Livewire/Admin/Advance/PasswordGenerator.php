<?php

namespace App\Livewire\Admin\Advance;

use Livewire\Component;

class PasswordGenerator extends Component
{
    public int $length = 16;

    public bool $includeUpper = true;

    public bool $includeLower = true;

    public bool $includeNumbers = true;

    public bool $includeSymbols = true;

    public bool $excludeSimilar = false;

    public string $password = '';

    protected const SIMILAR = 'il1Lo0O';

    protected const UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    protected const LOWER = 'abcdefghijklmnopqrstuvwxyz';

    protected const NUMBERS = '0123456789';

    protected const SYMBOLS = '!@#$%^&*()-_=+[]{}?';

    public function mount(): void
    {
        $this->generate();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['length', 'includeUpper', 'includeLower', 'includeNumbers', 'includeSymbols', 'excludeSimilar'], true)) {
            $this->generate();
        }
    }

    public function generate(): void
    {
        $this->validate([
            'length' => ['required', 'integer', 'min:6', 'max:128'],
        ]);

        $pool = '';
        $pool .= $this->includeUpper ? self::UPPER : '';
        $pool .= $this->includeLower ? self::LOWER : '';
        $pool .= $this->includeNumbers ? self::NUMBERS : '';
        $pool .= $this->includeSymbols ? self::SYMBOLS : '';

        if ($this->excludeSimilar) {
            $pool = str_replace(str_split(self::SIMILAR), '', $pool);
        }

        if ($pool === '') {
            $this->addError('options', 'Select at least one character type.');

            return;
        }

        $this->resetErrorBag('options');

        $poolLength = strlen($pool);
        $result = '';

        for ($i = 0; $i < $this->length; $i++) {
            $result .= $pool[random_int(0, $poolLength - 1)];
        }

        $this->password = $result;
    }

    public function getStrengthProperty(): string
    {
        $variety = $this->includeUpper + $this->includeLower + $this->includeNumbers + $this->includeSymbols;

        return match (true) {
            $this->length >= 14 && $variety >= 3 => 'Strong',
            $this->length >= 10 && $variety >= 2 => 'Good',
            default => 'Weak',
        };
    }

    public function render()
    {
        return view('livewire.admin.advance.password-generator')->layout('layouts.admin', ['title' => 'Password Generator']);
    }
}
