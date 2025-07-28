<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

const isLoaded = ref(false);

onMounted(() => {
    // Add loading animation
    setTimeout(() => {
        isLoaded.value = true;
    }, 100);
});
</script>

<template>
    <Head title="Welcome to WorkSphere" />

    <div class="min-h-screen antialiased bg-gradient-to-br from-slate-50 to-blue-50 dark:from-gray-900 dark:to-blue-900 flex overflow-hidden">
        <!-- Left Panel: Branding & Visuals with Auth Links -->
        <div class="w-full lg:w-1/2 bg-gradient-to-br from-blue-600 via-indigo-700 to-purple-800 relative flex items-center justify-center overflow-hidden">
            
            <!-- Animated Background Shapes -->
            <div class="absolute inset-0 z-0 opacity-30">
                <div class="absolute w-96 h-96 bg-white/10 rounded-full -top-48 -left-48 animate-pulse" style="animation-duration: 4s;"></div>
                <div class="absolute w-64 h-64 bg-white/5 rounded-full -bottom-32 -right-32 animate-pulse" style="animation-duration: 6s; animation-delay: 1s;"></div>
                <div class="absolute w-48 h-48 bg-white/10 rounded-full top-1/4 -right-24 animate-pulse" style="animation-duration: 5s; animation-delay: 2s;"></div>
                <div class="absolute w-32 h-32 bg-white/15 rounded-full bottom-1/3 -left-16 animate-pulse" style="animation-duration: 3s; animation-delay: 0.5s;"></div>
                
                <!-- Geometric patterns -->
                <div class="absolute inset-0">
                    <svg class="absolute top-20 left-20 w-8 h-8 text-white/20 animate-spin" style="animation-duration: 20s;" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <svg class="absolute bottom-32 right-16 w-6 h-6 text-white/15 animate-bounce" style="animation-duration: 3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>

            <!-- Content -->
            <div class="relative z-10 text-center text-white px-8 sm:px-12 py-12 max-w-md mx-auto" :class="{ 'animate-fade-in-up': isLoaded }">
                <!-- Logo with enhanced styling -->
                <div class="relative mb-8">
                    <div class="absolute inset-0 bg-white/20 rounded-full blur-xl"></div>
                    <div class="relative bg-white/10 backdrop-blur-sm rounded-3xl p-6 border border-white/20">
                        <svg class="h-16 w-16 mx-auto text-white drop-shadow-lg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                
                <h1 class="text-5xl font-bold tracking-tight mb-2 bg-gradient-to-r from-white to-blue-100 bg-clip-text text-transparent">
                    WorkSphere
                </h1>
                <p class="text-xl text-blue-100 mb-8 leading-relaxed">
                    The effortless way for your team to track time, manage projects, and boost productivity.
                </p>
                
                <!-- Feature highlights -->
                <div class="grid grid-cols-1 gap-4 mb-10 text-left">
                    <div class="flex items-center space-x-3 text-blue-100">
                        <div class="flex-shrink-0 w-2 h-2 bg-emerald-400 rounded-full"></div>
                        <span class="text-sm">Real-time collaboration</span>
                    </div>
                    <div class="flex items-center space-x-3 text-blue-100">
                        <div class="flex-shrink-0 w-2 h-2 bg-emerald-400 rounded-full"></div>
                        <span class="text-sm">Advanced analytics & reporting</span>
                    </div>
                    <div class="flex items-center space-x-3 text-blue-100">
                        <div class="flex-shrink-0 w-2 h-2 bg-emerald-400 rounded-full"></div>
                        <span class="text-sm">Smart project management</span>
                    </div>
                </div>
                
                <!-- Enhanced Auth Links -->
                <div class="space-y-4 max-w-xs mx-auto">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="route('dashboard')"
                            class="group w-full flex justify-center items-center py-4 px-6 border border-transparent rounded-xl shadow-lg text-base font-semibold text-blue-700 bg-white/95 backdrop-blur-sm hover:bg-white hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-white/50 transition-all duration-300 transform hover:scale-105"
                        >
                            <svg class="w-5 h-5 mr-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            Go to Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="group w-full flex justify-center items-center py-4 px-6 border border-transparent rounded-xl shadow-lg text-base font-semibold text-white bg-white/20 backdrop-blur-sm hover:bg-white/30 focus:outline-none focus:ring-4 focus:ring-white/50 transition-all duration-300 transform hover:scale-105"
                        >
                            <svg class="w-5 h-5 mr-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Sign In
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="group w-full flex justify-center items-center py-4 px-6 border-2 border-white/30 rounded-xl shadow-lg text-base font-semibold text-white hover:bg-white/10 focus:outline-none focus:ring-4 focus:ring-white/50 transition-all duration-300 transform hover:scale-105"
                        >
                            <svg class="w-5 h-5 mr-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Get Started Free
                        </Link>
                    </template>
                </div>
            </div>
        </div>

        <!-- Right Panel: Enhanced UI Mockup -->
        <div class="hidden lg:flex w-1/2 items-center justify-center p-12 relative">
            <!-- Background pattern -->
            <div class="absolute inset-0 opacity-5">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, #64748b 1px, transparent 0); background-size: 40px 40px;"></div>
            </div>
            
            <div class="w-full max-w-2xl transform transition-transform duration-700 hover:scale-105" :class="{ 'animate-slide-in-right': isLoaded }">
                <!-- Main dashboard mockup -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 overflow-hidden">
                    <!-- Window controls -->
                    <div class="p-6 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200/50 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="w-3 h-3 bg-red-400 rounded-full"></span>
                            <span class="w-3 h-3 bg-yellow-400 rounded-full"></span>
                            <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                        </div>
                        <div class="text-sm text-gray-500 font-medium">WorkSphere Dashboard</div>
                        <div class="w-6"></div>
                    </div>
                    
                    <!-- Dashboard content -->
                    <div class="p-8">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Dashboard Overview</h3>
                                <p class="text-sm text-gray-500 mt-1">Weekly Progress & Team Performance</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stats grid -->
                        <div class="grid grid-cols-3 gap-6 mb-8">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-600">24</div>
                                <div class="text-sm text-gray-500">Active Projects</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-emerald-600">89%</div>
                                <div class="text-sm text-gray-500">Completion Rate</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-purple-600">156h</div>
                                <div class="text-sm text-gray-500">This Week</div>
                            </div>
                        </div>
                        
                        <!-- Chart mockup -->
                        <div class="flex items-end space-x-3 h-32 mb-8">
                            <div class="w-full bg-gradient-to-t from-blue-400 to-blue-300 rounded-t-lg opacity-80" style="height: 45%;"></div>
                            <div class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t-lg" style="height: 70%;"></div>
                            <div class="w-full bg-gradient-to-t from-blue-400 to-blue-300 rounded-t-lg opacity-90" style="height: 55%;"></div>
                            <div class="w-full bg-gradient-to-t from-blue-600 to-blue-500 rounded-t-lg" style="height: 85%;"></div>
                            <div class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t-lg opacity-90" style="height: 75%;"></div>
                            <div class="w-full bg-gradient-to-t from-blue-400 to-blue-300 rounded-t-lg opacity-80" style="height: 60%;"></div>
                            <div class="w-full bg-gradient-to-t from-blue-600 to-blue-500 rounded-t-lg" style="height: 90%;"></div>
                        </div>
                        
                        <!-- Task list mockup -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-emerald-50 to-emerald-100 rounded-xl border border-emerald-200">
                                <div class="flex items-center space-x-4">
                                    <div class="w-6 h-6 rounded-full bg-emerald-500 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span class="text-gray-800 font-medium">Design new dashboard layout</span>
                                </div>
                                <span class="text-sm text-emerald-600 font-medium">Completed</span>
                            </div>
                            
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl border border-blue-200">
                                <div class="flex items-center space-x-4">
                                    <div class="w-6 h-6 rounded-full border-2 border-blue-500 bg-blue-500 animate-pulse"></div>
                                    <span class="text-gray-800 font-medium">Develop API endpoints</span>
                                </div>
                                <span class="text-sm text-blue-600 font-medium">In Progress</span>
                            </div>
                            
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl border border-gray-200">
                                <div class="flex items-center space-x-4">
                                    <div class="w-6 h-6 rounded-full border-2 border-gray-300"></div>
                                    <span class="text-gray-600">User testing & feedback</span>
                                </div>
                                <span class="text-sm text-gray-500">Pending</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Floating elements -->
                <div class="absolute -top-4 -right-4 w-16 h-16 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl shadow-lg animate-bounce" style="animation-duration: 3s;">
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                </div>
                
                <div class="absolute -bottom-6 -left-6 w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl shadow-lg animate-pulse">
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@keyframes fade-in-up {
    0% {
        opacity: 0;
        transform: translateY(30px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slide-in-right {
    0% {
        opacity: 0;
        transform: translateX(50px);
    }
    100% {
        opacity: 1;
        transform: translateX(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.8s ease-out;
}

.animate-slide-in-right {
    animation: slide-in-right 0.8s ease-out 0.2s both;
}
</style>