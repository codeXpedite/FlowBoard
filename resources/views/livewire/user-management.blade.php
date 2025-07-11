<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Kullanıcı Yönetimi</h2>
                    <a href="/dashboard" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                        Geri Dön
                    </a>
                </div>

                <!-- Flash Messages -->
                @if (session()->has('success'))
                    <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-md">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded-md">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Users Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kullanıcı
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    E-posta
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mevcut Rol
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rol Ata
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    İşlemler
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($users as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $user->name }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $user->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->roles->count() > 0)
                                            @foreach($user->roles as $role)
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    @if($role->name === 'Admin') bg-red-100 text-red-800
                                                    @elseif($role->name === 'Project Manager') bg-blue-100 text-blue-800
                                                    @else bg-green-100 text-green-800
                                                    @endif">
                                                    {{ $role->name }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Rol Yok
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <select wire:change="assignRole({{ $user->id }}, $event.target.value)" 
                                                class="text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Rol Seç</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @if($user->roles->count() > 0)
                                            <button wire:click="removeRole({{ $user->id }})"
                                                    wire:confirm="Bu kullanıcının rollerini kaldırmak istediğinizden emin misiniz?"
                                                    class="text-red-600 hover:text-red-900">
                                                Rol Kaldır
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Role Descriptions -->
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Rol Açıklamaları</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <h4 class="font-semibold text-red-800 mb-2">Admin</h4>
                            <p class="text-sm text-red-700">Tüm sisteme erişim. Kullanıcı yönetimi, proje ve görev işlemleri, sistem ayarları.</p>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-800 mb-2">Project Manager</h4>
                            <p class="text-sm text-blue-700">Proje oluşturma, görev yönetimi, kullanıcı davet etme, raporları görüntüleme.</p>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h4 class="font-semibold text-green-800 mb-2">Developer</h4>
                            <p class="text-sm text-green-700">Görevleri görüntüleme ve düzenleme, yorum yapma, kendi görevlerini yönetme.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
