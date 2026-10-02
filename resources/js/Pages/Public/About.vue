<!-- resources/js/Pages/Public/About.vue -->
<template>
    <PublicLayout>
        <Head title="About Omniscient" />

        <!-- ============== HERO ============== -->
        <section class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 text-white py-16 sm:py-20">
            <div class="absolute inset-0 opacity-[0.06]"
                 style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>
            <div class="absolute -top-32 -right-32 w-96 h-96 bg-primary-400/30 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-purple-400/20 rounded-full blur-3xl"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 mb-6 shadow-lg">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>

                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-xs font-medium tracking-wide uppercase text-primary-100 mb-4 backdrop-blur-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        About us
                    </span>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight leading-tight mb-4">
                        About Omniscient
                    </h1>
                    <p class="text-lg sm:text-xl text-primary-100 max-w-2xl mx-auto">
                        Helping people in Cameroon find what they need, and who can do it
                    </p>
                </div>
            </div>
        </section>

        <!-- ============== MISSION / VISION / VALUES ============== -->
        <section class="py-16 sm:py-20 bg-white dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Mission -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-8 text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 bg-blue-50 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center mx-auto mb-5">
                            <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight mb-2">Our Mission</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                            To make it easy to discover the people, businesses and services that can help, wherever you are in community.
                        </p>
                    </div>

                    <!-- Vision -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-8 text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center mx-auto mb-5">
                            <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight mb-2">Our Vision</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                            To become the go-to platform for business discovery and reviews across Africa, fostering economic growth and community connection.
                        </p>
                    </div>

                    <!-- Values -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-8 text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 bg-purple-50 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mx-auto mb-5">
                            <svg class="w-7 h-7 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight mb-2">Our Values</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                            Transparency, community, quality, and innovation. We believe in building trust through honest reviews and reliable information.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============== STORY ============== -->
        <section class="py-16 sm:py-20 bg-gray-50 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 overflow-hidden shadow-lg">
                    <div class="grid grid-cols-1 lg:grid-cols-2">

                        <!-- Story text -->
                        <div class="p-8 sm:p-12 flex flex-col justify-center">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-primary-600 dark:text-primary-400 mb-3">Our story</span>
                            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white tracking-tight mb-6">
                                From an idea to Cameroon's leading directory
                            </h2>
                            <div class="space-y-4 text-gray-600 dark:text-gray-400 leading-relaxed">
                                <p>
                                    Omniscient was born from a simple idea: making it easy for people to find and support local businesses in Cameroon. We realized that while there were many businesses offering great products and services, customers often struggled to find them.
                                </p>
                                <p>
                                    Starting in 2024, we built a platform that not only helps customers discover businesses but also gives business owners a powerful tool to grow their presence and connect with their community.
                                </p>
                                <p>
                                    Omniscient helps people in Cameroon discover the professionals, businesses, stores and places that can help them, and connect with them directly.
                                </p>
                            </div>
                        </div>

                        <!-- Stats panel -->
                        <div class="relative overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-purple-700 p-8 sm:p-12 flex items-center justify-center">
                            <div class="absolute inset-0 opacity-[0.06]"
                                 style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>
                            <div class="absolute -top-32 -right-32 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>

                            <div class="relative text-center text-white w-full">
                                <div class="py-6">
                                    <p class="text-5xl sm:text-6xl font-black tracking-tight mb-2">2024</p>
                                    <p class="text-lg font-semibold text-white/80">Founded</p>
                                </div>
                                <div class="h-px w-16 bg-white/20 mx-auto my-2"></div>
                                <div class="py-6">
                                    <p class="text-5xl sm:text-6xl font-black tracking-tight mb-2">500+</p>
                                    <p class="text-lg font-semibold text-white/80">Businesses listed</p>
                                </div>
                                <div class="h-px w-16 bg-white/20 mx-auto my-2"></div>
                                <div class="py-6">
                                    <p class="text-5xl sm:text-6xl font-black tracking-tight mb-2">1000+</p>
                                    <p class="text-lg font-semibold text-white/80">Happy customers</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============== TEAM ============== -->
        <section class="py-16 sm:py-20 bg-white dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12 max-w-2xl mx-auto">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-primary-600 dark:text-primary-400 mb-3 block">The team</span>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white tracking-tight mb-3">
                        Meet the people behind Omniscient
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        Passionate individuals dedicated to connecting communities and supporting local businesses.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="member in team" :key="member.initials"
                         class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <div class="w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-md"
                             :class="member.color">
                            <span class="text-2xl font-bold text-white">{{ member.initials }}</span>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white tracking-tight">{{ member.name }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ member.role }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============== CTA ============== -->
        <section class="relative overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-purple-700 py-16 sm:py-20">
            <div class="absolute inset-0 opacity-[0.06]"
                 style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>

            <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight mb-4">
                    Ready to get started?
                </h2>
                <p class="text-lg text-white/80 mb-8 max-w-2xl mx-auto">
                    Join thousands of businesses and customers on Omniscient today.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link href="/register"
                          class="group inline-flex items-center gap-2 px-8 py-4 bg-white text-primary-700 rounded-xl font-bold shadow-2xl hover:bg-gray-50 transition-all hover:-translate-y-0.5">
                        Create Your Account
                        <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </Link>
                    <Link href="/directory"
                          class="inline-flex items-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm text-white rounded-xl font-bold border border-white/20 hover:bg-white/20 transition-all">
                        Browse Directory
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup>
import { Link, Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const team = [
    { initials: 'JD', name: 'John Doe', role: 'CEO & Founder', color: 'bg-gradient-to-br from-primary-500 to-primary-700' },
    { initials: 'JS', name: 'Jane Smith', role: 'CTO', color: 'bg-gradient-to-br from-blue-500 to-indigo-600' },
    { initials: 'MK', name: 'Michael K.', role: 'Head of Operations', color: 'bg-gradient-to-br from-emerald-500 to-teal-600' },
    { initials: 'SN', name: 'Sarah N.', role: 'Community Manager', color: 'bg-gradient-to-br from-purple-500 to-pink-600' },
];
</script>