@extends('layouts.admin')

@section('title', 'Package Management')
@section('header', 'Package Management')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Total Packages</p>
                <p class="text-2xl font-bold text-white">{{ $stats['total'] }}</p>
            </div>
            <i class="fas fa-box text-3xl text-purple-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Active</p>
                <p class="text-2xl font-bold text-green-400">{{ $stats['active'] }}</p>
            </div>
            <i class="fas fa-check-circle text-3xl text-green-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Credit Packages</p>
                <p class="text-2xl font-bold text-blue-400">{{ $stats['credit_packages'] }}</p>
            </div>
            <i class="fas fa-coins text-3xl text-blue-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Subscriptions</p>
                <p class="text-2xl font-bold text-yellow-400">{{ $stats['subscription_packages'] }}</p>
            </div>
            <i class="fas fa-sync text-3xl text-yellow-400"></i>
        </div>
    </div>
</div>

<!-- Add Package Button -->
<div class="mb-6">
    <button onclick="openAddModal()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
        <i class="fas fa-plus mr-2"></i>Add New Package
    </button>
</div>

<!-- Credit Packages -->
<div class="bg-gray-800 rounded-lg border border-gray-700 mb-6">
    <div class="px-4 py-3 border-b border-gray-700">
        <h3 class="text-lg font-semibold text-white">
            <i class="fas fa-coins mr-2 text-yellow-400"></i>Credit Packages (One-time Purchase)
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Package</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Price</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Credits</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Bonus</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase">Featured</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @foreach($packages->where('type', 'credits') as $package)
                <tr class="hover:bg-gray-750">
                    <td class="px-4 py-4">
                        <p class="font-medium text-white">{{ $package->name }}</p>
                        <p class="text-xs text-gray-400">{{ $package->slug }}</p>
                    </td>
                    <td class="px-4 py-4">
                        <span class="text-green-400 font-bold">${{ number_format($package->price, 2) }}</span>
                        @if($package->original_price)
                        <span class="text-gray-500 text-sm line-through ml-1">${{ number_format($package->original_price, 2) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-yellow-400 font-bold">{{ number_format($package->credits_amount) }}</td>
                    <td class="px-4 py-4 text-purple-400">+{{ number_format($package->bonus_credits) }}</td>
                    <td class="px-4 py-4 text-center">
                        <button onclick="togglePackage({{ $package->id }})" id="status-{{ $package->id }}"
                            class="px-3 py-1 text-xs rounded-full {{ $package->is_active ? 'bg-green-600/20 text-green-400' : 'bg-red-600/20 text-red-400' }}">
                            {{ $package->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td class="px-4 py-4 text-center">
                        @if($package->is_featured)
                        <i class="fas fa-star text-yellow-400"></i>
                        @else
                        <i class="fas fa-star text-gray-600"></i>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-center">
                        <button onclick="editPackage({{ $package->id }}, {{ json_encode($package) }})" class="text-blue-400 hover:text-blue-300 mr-2">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('admin.packages.delete', $package) }}" method="POST" class="inline" onsubmit="return confirm('Delete this package?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-300">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Subscription Packages -->
<div class="bg-gray-800 rounded-lg border border-gray-700">
    <div class="px-4 py-3 border-b border-gray-700 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-white">
            <i class="fas fa-sync mr-2 text-blue-400"></i>Subscription Packages
        </h3>
        <span class="px-2 py-1 bg-orange-600/20 text-orange-400 text-xs rounded">Not Active - Coming Soon</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Package</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Price</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Period</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Monthly Credits</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Priority</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @foreach($packages->where('type', 'subscription') as $package)
                <tr class="hover:bg-gray-750 opacity-60">
                    <td class="px-4 py-4">
                        <p class="font-medium text-white">{{ $package->name }}</p>
                        <p class="text-xs text-gray-400">{{ $package->description }}</p>
                    </td>
                    <td class="px-4 py-4">
                        <span class="text-green-400 font-bold">${{ number_format($package->price, 2) }}</span>
                        @if($package->original_price)
                        <span class="text-gray-500 text-sm line-through ml-1">${{ number_format($package->original_price, 2) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-4">
                        <span class="px-2 py-1 bg-blue-600/20 text-blue-400 text-xs rounded">{{ ucfirst($package->billing_period) }}</span>
                    </td>
                    <td class="px-4 py-4 text-yellow-400 font-bold">{{ number_format($package->monthly_credits) }}</td>
                    <td class="px-4 py-4">
                        <span class="px-2 py-1 {{ $package->priority_level >= 50 ? 'bg-purple-600/20 text-purple-400' : 'bg-gray-600/20 text-gray-400' }} text-xs rounded">
                            Level {{ $package->priority_level }}
                        </span>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <button onclick="togglePackage({{ $package->id }})" id="status-{{ $package->id }}"
                            class="px-3 py-1 text-xs rounded-full {{ $package->is_active ? 'bg-green-600/20 text-green-400' : 'bg-red-600/20 text-red-400' }}">
                            {{ $package->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <button onclick="editPackage({{ $package->id }}, {{ json_encode($package) }})" class="text-blue-400 hover:text-blue-300 mr-2">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="package-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-full max-w-lg mx-4 border border-gray-700">
        <h3 class="text-xl font-semibold text-white mb-4" id="modal-title">Add New Package</h3>
        <form id="package-form" method="POST">
            @csrf
            <div id="method-field"></div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Name</label>
                        <input type="text" name="name" id="pkg-name" required
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Slug</label>
                        <input type="text" name="slug" id="pkg-slug" required
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Type</label>
                        <select name="type" id="pkg-type" required
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
                            <option value="credits">Credits (One-time)</option>
                            <option value="subscription">Subscription</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Billing Period</label>
                        <select name="billing_period" id="pkg-period" required
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
                            <option value="one_time">One Time</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Price (USD)</label>
                    <input type="number" name="price" id="pkg-price" step="0.01" min="0" required
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Credits Amount</label>
                        <input type="number" name="credits_amount" id="pkg-credits" min="0" value="0"
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Monthly Credits</label>
                        <input type="number" name="monthly_credits" id="pkg-monthly" min="0" value="0"
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                    <textarea name="description" id="pkg-desc" rows="2"
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"></textarea>
                </div>

                <div class="flex items-center gap-6">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" id="pkg-active" value="1"
                            class="w-5 h-5 rounded border-gray-600 bg-gray-700 text-green-500">
                        <span class="ml-2 text-gray-300">Active</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_featured" id="pkg-featured" value="1"
                            class="w-5 h-5 rounded border-gray-600 bg-gray-700 text-yellow-500">
                        <span class="ml-2 text-gray-300">Featured</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                    <i class="fas fa-save mr-2"></i>Save Package
                </button>
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = '{{ csrf_token() }}';

function openAddModal() {
    document.getElementById('modal-title').textContent = 'Add New Package';
    document.getElementById('package-form').action = '{{ route("admin.packages.store") }}';
    document.getElementById('method-field').innerHTML = '';

    // Reset form
    document.getElementById('pkg-name').value = '';
    document.getElementById('pkg-slug').value = '';
    document.getElementById('pkg-type').value = 'credits';
    document.getElementById('pkg-period').value = 'one_time';
    document.getElementById('pkg-price').value = '';
    document.getElementById('pkg-credits').value = '0';
    document.getElementById('pkg-monthly').value = '0';
    document.getElementById('pkg-desc').value = '';
    document.getElementById('pkg-active').checked = true;
    document.getElementById('pkg-featured').checked = false;
    document.getElementById('pkg-slug').disabled = false;

    showModal();
}

function editPackage(id, pkg) {
    document.getElementById('modal-title').textContent = 'Edit Package';
    document.getElementById('package-form').action = `/admin/packages/${id}`;
    document.getElementById('method-field').innerHTML = '@method("PUT")';

    document.getElementById('pkg-name').value = pkg.name;
    document.getElementById('pkg-slug').value = pkg.slug;
    document.getElementById('pkg-slug').disabled = true;
    document.getElementById('pkg-type').value = pkg.type;
    document.getElementById('pkg-period').value = pkg.billing_period;
    document.getElementById('pkg-price').value = pkg.price;
    document.getElementById('pkg-credits').value = pkg.credits_amount || 0;
    document.getElementById('pkg-monthly').value = pkg.monthly_credits || 0;
    document.getElementById('pkg-desc').value = pkg.description || '';
    document.getElementById('pkg-active').checked = pkg.is_active;
    document.getElementById('pkg-featured').checked = pkg.is_featured;

    showModal();
}

function showModal() {
    document.getElementById('package-modal').classList.remove('hidden');
    document.getElementById('package-modal').classList.add('flex');
}

function closeModal() {
    document.getElementById('package-modal').classList.add('hidden');
    document.getElementById('package-modal').classList.remove('flex');
}

async function togglePackage(id) {
    try {
        const response = await fetch(`/admin/packages/${id}/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        });

        const data = await response.json();

        if (data.success) {
            const btn = document.getElementById(`status-${id}`);
            btn.textContent = data.is_active ? 'Active' : 'Inactive';
            btn.className = `px-3 py-1 text-xs rounded-full ${data.is_active ? 'bg-green-600/20 text-green-400' : 'bg-red-600/20 text-red-400'}`;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Auto-generate slug from name
document.getElementById('pkg-name').addEventListener('input', function() {
    if (!document.getElementById('pkg-slug').disabled) {
        document.getElementById('pkg-slug').value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }
});
</script>
@endpush
