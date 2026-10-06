<x-layouts.guest title="{{ __('Reset password') }}">

    <a href="{{ route('login') }}" class="text-[15px] font-semibold text-text-secondary">‹ {{ __('Sign in') }}</a>
    <h1 class="mt-3 text-[26px] font-bold tracking-title text-ink">{{ __('Set a new password') }}</h1>
    <p class="mt-1.5 text-[15px] text-text-secondary">{{ __('For :email', ['email' => old('email', $email)]) }}</p>

    @if($errors->any())
        <div class="mt-5 flex flex-col gap-1.5 p-3.5 rounded-field bg-danger-tint border border-danger-tint-border">
            @foreach($errors->all() as $error)
                <div class="flex items-start gap-2.5">
                    <span class="mt-[7px] w-2 h-2 rounded-full bg-danger shrink-0"></span>
                    <p class="text-[14px] leading-normal text-danger">{{ $error }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" class="mt-5 flex flex-col gap-3.5"
          x-data="{
              pw: '',
              get score() {
                  let s = 0;
                  if (this.pw.length >= 8) s++;
                  if (/[a-zA-Z]/.test(this.pw)) s++;
                  if (/[0-9]/.test(this.pw)) s++;
                  if (/[^a-zA-Z0-9]/.test(this.pw)) s++;
                  return s;
              },
              get valid() { return this.score >= 3; }
          }">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ old('email', $email) }}">

        <div>
            <label for="password" class="block mb-1.5 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('New Password') }}</label>
            <x-password-input id="password" name="password" autocomplete="new-password" placeholder="{{ __('Min. 8 characters') }}" required :hasError="$errors->has('password')" x-on:input="pw = $event.target.value" />
            <x-password-strength />
        </div>

        <button type="submit" :disabled="!valid" class="mt-1.5 h-[50px] rounded-field bg-brand text-[15px] font-semibold text-white disabled:opacity-50">
            {{ __('Save Password') }}
        </button>
    </form>

</x-layouts.guest>
