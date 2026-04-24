<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Wallets') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <header>
                    <h2 class="text-lg font-medium text-gray-900">Add New Wallet</h2>
                </header>

                <form method="post" action="{{ route('wallets.store') }}" class="mt-6 space-y-6">
                    @csrf
                    <div>
                        <x-input-label for="name" :value="__('Wallet Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" placeholder="Misal: Dompet Utama, Bank BCA" required />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="balance" :value="__('Initial Balance')" />
                            <x-text-input id="balance" name="balance" type="number" step="0.01" class="mt-1 block w-full" value="0" required />
                        </div>
                        <div>
                            <x-input-label for="group_type" :value="__('Group Type')" />
                            <select id="group_type" name="group_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="Liquid">Liquid (Cash/Debit)</option>
                                <option value="Investment">Investment</option>
                                <option value="Debt">Debt</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="icon" :value="__('Icon (Emoji or URL)')" />
                        <x-text-input id="icon" name="icon" type="text" class="mt-1 block w-full" placeholder="💰, 💳, atau link gambar" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save Wallet') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="p-2">Icon</th>
                            <th class="p-2">Name</th>
                            <th class="p-2">Type</th>
                            <th class="p-2 text-right">Balance</th>
                            <th class="p-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($wallets as $wallet)
                        @php
                            // LOGIKA CEK GAMBAR (Harus di dalam perulangan $category)
                            $rawIcon = $category->icon ?? '📁';
                            $isImage = \Illuminate\Support\Str::contains($rawIcon, ['.png', '.jpg', '.jpeg', '.webp', '/']);
                        @endphp
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-2 text-2xl"> @if($isImage)
                                    <img src="{{ asset('storage/' . $rawIcon) }}" class="w-full h-full object-cover rounded-xl">
                                @else
                                    {{ $rawIcon }}
                                @endif
                            </td>
                            <td class="p-2 font-bold">{{ $wallet->name }}</td>
                            <td class="p-2 text-sm text-gray-600">{{ $wallet->group_type }}</td>
                            <td class="p-2 text-right">Rp {{ number_format($wallet->balance, 0, ',', '.') }}</td>
                            <td class="p-2 text-center">
                                <form method="POST" action="{{ route('wallets.destroy', $wallet) }}" onsubmit="return confirm('Hapus wallet ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>