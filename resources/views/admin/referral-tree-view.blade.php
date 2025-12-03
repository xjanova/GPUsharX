@extends('layouts.admin')

@section('title', 'Referral Network Tree')
@section('header', 'Referral Network Tree')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Total Users</p>
                <p class="text-2xl font-bold text-white">{{ number_format($stats['total_users']) }}</p>
            </div>
            <i class="fas fa-users text-3xl text-blue-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">With Referrer</p>
                <p class="text-2xl font-bold text-green-400">{{ number_format($stats['users_with_referrer']) }}</p>
            </div>
            <i class="fas fa-user-plus text-3xl text-green-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">With Referrals</p>
                <p class="text-2xl font-bold text-purple-400">{{ number_format($stats['users_with_referrals']) }}</p>
            </div>
            <i class="fas fa-sitemap text-3xl text-purple-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Commission Paid</p>
                <p class="text-2xl font-bold text-yellow-400">${{ number_format($stats['total_commission_paid'], 2) }}</p>
            </div>
            <i class="fas fa-money-bill-wave text-3xl text-yellow-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Pending</p>
                <p class="text-2xl font-bold text-orange-400">${{ number_format($stats['pending_commission'], 2) }}</p>
            </div>
            <i class="fas fa-clock text-3xl text-orange-400"></i>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Tree Visualization -->
    <div class="lg:col-span-2 bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-sitemap mr-2 text-purple-400"></i>Network Graph
            </h3>
            <div class="flex gap-2">
                <button onclick="zoomIn()" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">
                    <i class="fas fa-search-plus"></i>
                </button>
                <button onclick="zoomOut()" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">
                    <i class="fas fa-search-minus"></i>
                </button>
                <button onclick="resetZoom()" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">
                    <i class="fas fa-compress-arrows-alt"></i>
                </button>
            </div>
        </div>
        <div id="network-graph" style="height: 500px; background: #1f2937;"></div>
    </div>

    <!-- Top Referrers -->
    <div class="bg-gray-800 rounded-lg border border-gray-700">
        <div class="px-4 py-3 border-b border-gray-700">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-trophy mr-2 text-yellow-400"></i>Top Referrers
            </h3>
        </div>
        <div class="p-4">
            @if(count($topReferrers) > 0)
            <div class="space-y-3">
                @foreach($topReferrers as $index => $user)
                <div class="flex items-center gap-3 p-3 bg-gray-750 rounded-lg hover:bg-gray-700 transition cursor-pointer"
                     onclick="focusNode({{ $user->id }})">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                        {{ $index === 0 ? 'bg-yellow-500' : ($index === 1 ? 'bg-gray-400' : ($index === 2 ? 'bg-orange-600' : 'bg-gray-600')) }}">
                        <span class="text-white font-bold text-sm">{{ $index + 1 }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-medium truncate">{{ $user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $user->referral_code }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-purple-400 font-bold">{{ $user->direct_referrals_count }}</p>
                        <p class="text-xs text-gray-500">referrals</p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-400 text-center py-8">No referrals yet</p>
            @endif
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div id="user-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 border border-gray-700">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-white" id="modal-name"></h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-400">Email:</span>
                <span class="text-white" id="modal-email"></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Referral Code:</span>
                <span class="text-purple-400 font-mono" id="modal-code"></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Direct Referrals:</span>
                <span class="text-green-400 font-bold" id="modal-referrals"></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Credits:</span>
                <span class="text-yellow-400" id="modal-credits"></span>
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <a id="modal-view-link" href="#" class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-center transition">
                <i class="fas fa-user mr-2"></i>View Details
            </a>
            <button onclick="closeModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition">
                Close
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
<script>
const nodesData = @json($nodes);
const edgesData = @json($edges);

// Create vis.js DataSets
const nodes = new vis.DataSet(nodesData.map(node => ({
    id: node.id,
    label: node.name,
    title: `${node.name}\n${node.email}\nReferrals: ${node.referrals}`,
    color: {
        background: node.referrals > 5 ? '#8B5CF6' : (node.referrals > 0 ? '#3B82F6' : '#374151'),
        border: node.referrals > 5 ? '#A78BFA' : (node.referrals > 0 ? '#60A5FA' : '#4B5563'),
        highlight: { background: '#F59E0B', border: '#FBBF24' }
    },
    font: { color: '#E5E7EB' },
    size: 15 + Math.min(node.referrals * 2, 20),
    data: node
})));

const edges = new vis.DataSet(edgesData.map((edge, i) => ({
    id: i,
    from: edge.from,
    to: edge.to,
    arrows: 'to',
    color: { color: '#6B7280', highlight: '#F59E0B' }
})));

// Create network
const container = document.getElementById('network-graph');
const data = { nodes, edges };
const options = {
    physics: {
        stabilization: { iterations: 100 },
        barnesHut: {
            gravitationalConstant: -5000,
            springLength: 150,
            springConstant: 0.01
        }
    },
    interaction: {
        hover: true,
        tooltipDelay: 100
    },
    layout: {
        improvedLayout: true
    }
};

const network = new vis.Network(container, data, options);

// Click event
network.on('click', function(params) {
    if (params.nodes.length > 0) {
        const nodeId = params.nodes[0];
        const node = nodes.get(nodeId);
        showModal(node.data);
    }
});

function showModal(user) {
    document.getElementById('modal-name').textContent = user.name;
    document.getElementById('modal-email').textContent = user.email;
    document.getElementById('modal-code').textContent = user.referral_code;
    document.getElementById('modal-referrals').textContent = user.referrals;
    document.getElementById('modal-credits').textContent = user.credits.toLocaleString();
    document.getElementById('modal-view-link').href = `/admin/users/${user.id}`;

    document.getElementById('user-modal').classList.remove('hidden');
    document.getElementById('user-modal').classList.add('flex');
}

function closeModal() {
    document.getElementById('user-modal').classList.add('hidden');
    document.getElementById('user-modal').classList.remove('flex');
}

function focusNode(nodeId) {
    network.focus(nodeId, {
        scale: 1.5,
        animation: {
            duration: 500,
            easingFunction: 'easeInOutQuad'
        }
    });
    network.selectNodes([nodeId]);

    const node = nodes.get(nodeId);
    if (node) showModal(node.data);
}

function zoomIn() {
    network.moveTo({ scale: network.getScale() * 1.3 });
}

function zoomOut() {
    network.moveTo({ scale: network.getScale() / 1.3 });
}

function resetZoom() {
    network.fit({ animation: true });
}
</script>
@endpush
