<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GPU Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="/" class="inline-flex items-center text-2xl font-bold">
                <i class="fas fa-microchip text-purple-500 mr-3"></i>
                GPU Share
            </a>
        </div>

        <div class="bg-gray-800 rounded-2xl border border-gray-700 p-8">
            <h2 class="text-2xl font-bold mb-6 text-center">Create Account</h2>

            @if($errors->any())
            <div class="bg-red-500/20 border border-red-500 text-red-400 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="/register">
                @csrf
                <div class="mb-4">
                    <label class="block text-gray-400 text-sm mb-2">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Your name">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-400 text-sm mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="your@email.com">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-400 text-sm mb-2">Password</label>
                    <input type="password" name="password" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Min 8 characters">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-400 text-sm mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Confirm your password">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-400 text-sm mb-2">Referral Code (Optional)</label>
                    <input type="text" name="referral_code" value="{{ request('ref') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Enter referral code">
                </div>

                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 py-3 rounded-lg font-medium">
                    Create Account
                </button>
            </form>

            <div class="mt-6 text-center text-gray-400">
                Already have an account?
                <a href="/login" class="text-purple-400 hover:text-purple-300">Login</a>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="/" class="text-gray-500 hover:text-gray-400">
                <i class="fas fa-arrow-left mr-2"></i>Back to Home
            </a>
        </div>
    </div>
</body>
</html>
