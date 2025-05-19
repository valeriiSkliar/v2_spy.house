<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Login -->
        <div>
            <x-input-label for="login" :value="__('Login')" />
            <x-text-input id="login" class="block mt-1 w-full" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <!-- Messenger Type -->
        <div class="mt-4">
            <x-input-label for="messenger_type" :value="__('Messenger Type')" />
            <select id="messenger_type" name="messenger_type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                <option value="" disabled {{ old('messenger_type') ? '' : 'selected' }}>{{ __('Select messenger') }}</option>
                <option value="telegram" {{ old('messenger_type') == 'telegram' ? 'selected' : '' }}>Telegram</option>
                <option value="whatsapp" {{ old('messenger_type') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                <option value="viber" {{ old('messenger_type') == 'viber' ? 'selected' : '' }}>Viber</option>
            </select>
            <x-input-error :messages="$errors->get('messenger_type')" class="mt-2" />
        </div>

        <!-- Messenger Contact -->
        <div class="mt-4">
            <x-input-label for="messenger_contact" :value="__('Messenger Contact')" />
            <x-text-input id="messenger_contact" class="block mt-1 w-full" type="text" name="messenger_contact" :value="old('messenger_contact')" required />
            <x-input-error :messages="$errors->get('messenger_contact')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Experience -->
        <div class="mt-4">
            <x-input-label for="experience" :value="__('Experience')" />
            <select id="experience" name="experience" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                <option value="" disabled {{ old('experience') ? '' : 'selected' }}>{{ __('Select experience level') }}</option>
                @foreach(\App\Enums\Frontend\UserExperience::cases() as $experience)
                    <option value="{{ $experience->name }}" {{ old('experience') == $experience->name ? 'selected' : '' }}>
                        {{ $experience->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('experience')" class="mt-2" />
        </div>

        <!-- Scope of Activity -->
        <div class="mt-4">
            <x-input-label for="scope_of_activity" :value="__('Scope of Activity')" />
            <select id="scope_of_activity" name="scope_of_activity" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                <option value="" disabled {{ old('scope_of_activity') ? '' : 'selected' }}>{{ __('Select scope of activity') }}</option>
                @foreach(\App\Enums\Frontend\UserScopeOfActivity::cases() as $scope)
                    <option value="{{ $scope->name }}" {{ old('scope_of_activity') == $scope->name ? 'selected' : '' }}>
                        {{ $scope->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('scope_of_activity')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
