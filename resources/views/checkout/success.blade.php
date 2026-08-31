<x-app-layout>
    <div class="flex items-center justify-center min-h-[60vh]">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                {{-- Animated Checkmark --}}
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                    <x-icon name="check-badge" solid class="h-12 w-12 text-green-500 animate-bounce" />
                </div>

                {{-- Title --}}
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Successful!</h1>
                <p class="text-gray-600 mb-8">
                    Terima kasih! Subscription kamu sudah aktif.
                </p>

                {{-- Countdown --}}
                <div class="mb-6" x-data="{ count: 3 }" x-init="
                    setInterval(() => {
                        count--;
                        if (count <= 0) window.location.href = '{{ route('dashboard') }}';
                    }, 1000)
                ">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-full text-sm text-gray-600">
                        <svg class="animate-spin h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span>Redirect ke dashboard dalam <span class="font-bold text-indigo-600" x-text="count">3</span> detik...</span>
                    </div>
                </div>

                {{-- Dashboard Button --}}
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-lg hover:shadow-xl">
                    <x-icon name="home" class="w-5 h-5" />
                    Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
