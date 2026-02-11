<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>{{ __('Period Information') }}</span>
            </div>
        </x-slot>

        @php
            $data = $this->getData();
        @endphp

        <div class="space-y-4">
            @if($data['currentPeriod'])
                <div class="flex w-full">
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Current Period') }} : </span>
                        <span class="text-lg font-semibold">{{ $data['currentPeriod']->name }}</span>
                    </div>

                    <div>
                        <x-filament::button size="sm" color="gray" icon="heroicon-m-pencil-square" :href="$this->getPeriodsUrl()" tag="a">
                            {{ __('Change') }}
                        </x-filament::button>
                    </div>
                </div>


            @endif

            @if($data['enrollmentsPeriod'])
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Enrollments Period') }}</p>
                    <p class="text-lg font-semibold">{{ $data['enrollmentsPeriod']->name }}</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
