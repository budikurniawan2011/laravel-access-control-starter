<x-filament-panels::layout.simple max-content-width="md">
    <div class="fi-simple-page">
        <div class="fi-simple-page-content">
            <x-filament-panels::header.simple
                heading="Initial setup"
                subheading="Create the first administrator account to get started."
            />

            <x-filament::section
                heading="Administrator account"
                description="This one-time setup page will be locked after completion."
            >
                <div class="fi-sc fi-sc-has-gap" style="display: grid;">
                    @if ($errors->any())
                        <x-filament::callout color="danger" heading="Check the form">
                            <ul class="list-inside list-disc">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-filament::callout>
                    @endif

                    <form method="POST" action="{{ route('setup.store') }}" class="fi-sc fi-sc-has-gap" style="display: grid;">
                        @csrf

                        @if (config('app.setup_key'))
                            <x-filament-forms::field-wrapper
                                id="setup_key"
                                label="Setup key"
                                :error-messages="$errors->get('setup_key')"
                            >
                                <x-filament::input.wrapper :valid="! $errors->has('setup_key')">
                                    <x-filament::input
                                        id="setup_key"
                                        name="setup_key"
                                        type="password"
                                        required
                                        autocomplete="off"
                                    />
                                </x-filament::input.wrapper>
                            </x-filament-forms::field-wrapper>
                        @endif

                        <x-filament-forms::field-wrapper
                            id="name"
                            label="Name"
                            :error-messages="$errors->get('name')"
                        >
                            <x-filament::input.wrapper :valid="! $errors->has('name')">
                                <x-filament::input
                                    id="name"
                                    name="name"
                                    :value="old('name')"
                                    required
                                    maxlength="255"
                                    autocomplete="name"
                                />
                            </x-filament::input.wrapper>
                        </x-filament-forms::field-wrapper>

                        <x-filament-forms::field-wrapper
                            id="email"
                            label="Email"
                            :error-messages="$errors->get('email')"
                        >
                            <x-filament::input.wrapper :valid="! $errors->has('email')">
                                <x-filament::input
                                    id="email"
                                    name="email"
                                    type="email"
                                    :value="old('email')"
                                    required
                                    maxlength="255"
                                    autocomplete="email"
                                />
                            </x-filament::input.wrapper>
                        </x-filament-forms::field-wrapper>

                        <x-filament-forms::field-wrapper
                            id="password"
                            label="Password"
                            :error-messages="$errors->get('password')"
                        >
                            <x-filament::input.wrapper :valid="! $errors->has('password')">
                                <x-filament::input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    minlength="12"
                                    autocomplete="new-password"
                                />
                            </x-filament::input.wrapper>
                            <p style="color: var(--gray-500); font-size: 0.875rem;">
                                At least 12 characters with uppercase, lowercase, a number, and a symbol.
                            </p>
                        </x-filament-forms::field-wrapper>

                        <x-filament-forms::field-wrapper
                            id="password_confirmation"
                            label="Confirm password"
                            :error-messages="$errors->get('password_confirmation')"
                        >
                            <x-filament::input.wrapper :valid="! $errors->has('password_confirmation')">
                                <x-filament::input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    required
                                    minlength="12"
                                    autocomplete="new-password"
                                />
                            </x-filament::input.wrapper>
                        </x-filament-forms::field-wrapper>

                        <x-filament::button type="submit" size="lg" style="width: 100%;">
                            Create administrator
                        </x-filament::button>
                    </form>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::layout.simple>
