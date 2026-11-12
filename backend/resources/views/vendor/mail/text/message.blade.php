<x-mail::layout>
    {{-- Kepala --}}
    <x-slot:header>
        <x-mail::header :url="config('app.frontend_url')">
            Al-Futuuhat
        </x-mail::header>
    </x-slot:header>

    {{-- Isi --}}
    {{ $slot }}

    {{-- Catatan bawah --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Kaki --}}
    <x-slot:footer>
        <x-mail::footer>
            © {{ date('Y') }} Al-Futuuhat. @lang('All rights reserved.')
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
