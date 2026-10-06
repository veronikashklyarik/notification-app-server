<?php

namespace App\Livewire;

use App\Enums\Locale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Settings extends Component
{
    public string $timezone = '';

    public string $locale = 'en';

    public function mount(): void
    {
        $user = Auth::user();
        $this->timezone = $user->timezone ?? 'UTC';
        $this->locale = $user->locale ?? 'en';
    }

    public function updateTimezone(): void
    {
        $this->validateOnly('timezone', ['timezone' => 'required|string|timezone']);

        Auth::user()->update(['timezone' => $this->timezone]);

        $this->redirect(route('settings'));
    }

    public function updateLang(): void
    {
        $this->validateOnly('locale', [
            'locale' => ['required', Rule::enum(Locale::class)],
        ]);

        Auth::user()->update(['locale' => $this->locale]);

        $this->redirect(route('settings'));
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('login'));
    }

    public function render(): View
    {
        return view('livewire.settings', [
            'user' => Auth::user(),
            'timezoneGroups' => $this->groupedTimezones(),
            'appVersion' => trim((string) file_get_contents(base_path('VERSION'))),
        ]);
    }

    /**
     * @return array<string, array<int, array{value: string, label: string, offset: int}>>
     */
    private function groupedTimezones(): array
    {
        $regionMap = [
            'Europe' => ['Europe', 'Atlantic', 'Arctic', 'UTC'],
            'Americas' => ['America', 'Antarctica'],
            'Asia and Pacific' => ['Asia', 'Pacific', 'Australia', 'Indian'],
            'Africa' => ['Africa'],
        ];

        $groups = array_fill_keys(array_keys($regionMap), []);

        foreach (timezone_identifiers_list() as $identifier) {
            $prefix = explode('/', $identifier)[0];
            $region = collect($regionMap)->search(fn ($prefixes) => in_array($prefix, $prefixes, true)) ?: 'Asia and Pacific';

            $city = str_replace('_', ' ', last(explode('/', $identifier)));
            $offset = (new \DateTime('now', new \DateTimeZone($identifier)))->getOffset() / 60;
            $sign = $offset >= 0 ? '+' : '-';
            $hours = intdiv(abs($offset), 60);
            $minutes = abs($offset) % 60;
            $offsetLabel = 'UTC'.$sign.$hours.($minutes ? ':'.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) : '');

            $groups[$region][] = [
                'value' => $identifier,
                'label' => "{$city} · {$offsetLabel}",
                'offset' => $offset,
            ];
        }

        foreach ($groups as &$zones) {
            usort($zones, fn ($a, $b) => $a['offset'] <=> $b['offset'] ?: strcmp($a['label'], $b['label']));
        }

        return $groups;
    }
}
