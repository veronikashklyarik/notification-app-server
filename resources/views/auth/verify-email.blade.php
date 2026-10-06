<x-layouts.guest title="{{ __('Verify email') }}">

    <div class="mb-5 w-9 h-9 rounded-field bg-indigo-tint flex items-center justify-center">
        <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
        </svg>
    </div>
    <h1 class="text-[26px] font-bold tracking-title text-ink">{{ __('One last step') }}</h1>
    <p class="mt-2 text-[15px] leading-normal text-text-secondary">
        {{ __('Tap the link sent to') }} <strong class="text-ink">{{ auth()->user()->email }}</strong>. {{ __('You can create reminders right now — this screen moves on by itself once the email is confirmed.') }}
    </p>

    @if(session('status') === 'A new verification link has been sent to your email address.')
        <div class="mt-4 flex items-center gap-2.5 p-3.5 rounded-field bg-success-tint border border-success/20">
            <svg class="w-4 h-4 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <p class="text-[13px] text-success">{{ session('status') }}</p>
        </div>
    @endif

    <div class="mt-5 flex flex-col gap-2.5">
        <a href="{{ route('home') }}" class="flex items-center justify-center h-[50px] rounded-field bg-brand text-[15px] font-semibold text-white">
            {{ __('Continue to :appName', ['appName' => config('app.name')]) }}
        </a>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="w-full h-[50px] rounded-field bg-white border border-border text-[15px] font-semibold text-text-secondary">
                {{ __('Resend email') }}
            </button>
        </form>
    </div>

    <p class="mt-5 text-[14px] text-center text-text-secondary">
        {{ __('Typo in the address?') }}
        <a href="{{ route('profile.edit') }}" class="font-semibold text-brand">{{ __('Change email') }}</a>
    </p>

</x-layouts.guest>
