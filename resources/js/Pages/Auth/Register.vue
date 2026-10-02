<!-- resources/js/Pages/Auth/Register.vue -->
<template>
    <GuestLayout>

        <Head title="Create Account" />

        <div class="flex items-center justify-center min-h-[calc(100vh-12rem)] py-8">
            <div class="w-full max-w-md">

                <!-- HEADER -->
                <div class="text-center mb-8">
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-50 dark:bg-primary-900/30 border border-primary-100 dark:border-primary-800 text-xs font-bold uppercase tracking-widest text-primary-700 dark:text-primary-300 mb-4">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse"></span>
                        Get started
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white tracking-tight">
                        {{ accountType === 'user' ? 'Join Omniscient' : 'Create your discoverable account' }}
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ accountType === 'owner'
                            ? 'Your Listing is what people find on Omniscient'
                            : 'Save favorites, leave reviews, and discover what you need' }}
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-black/30 p-6 sm:p-8">

                    <!-- ACCOUNT TYPE SELECTOR -->
                    <div class="mb-6">
                        <p class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3">
                            What brings you here today?
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- User -->
                            <button type="button" @click="accountType = 'user'; form.account_type = 'user'"
                                class="relative p-4 rounded-2xl border-2 transition-all text-left" :class="accountType === 'user'
                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'">
                                <div class="flex items-center gap-2.5 mb-2">
                                    <span class="text-2xl">🔍</span>
                                    <span class="font-bold text-gray-900 dark:text-white text-sm tracking-tight">Browsing</span>
                                    <div class="ml-auto w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                        :class="accountType === 'user' ? 'border-primary-500 bg-primary-500' : 'border-gray-300 dark:border-gray-600'">
                                        <svg v-if="accountType === 'user'" class="w-3 h-3 text-white" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                    Save favorites, leave reviews, and contact listings.
                                </p>
                            </button>


                            <!-- Professional (PHASE 19B) -->
                            <button type="button" @click="accountType = 'professional'; form.account_type = 'professional'"
                                class="relative p-4 rounded-2xl border-2 transition-all text-left" :class="accountType === 'professional'
                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'">
                                <div class="flex items-center gap-2.5 mb-2">
                                    <span class="text-2xl">🧑‍🔧</span>
                                    <span class="font-bold text-gray-900 dark:text-white text-sm tracking-tight">A professional</span>
                                    <div class="ml-auto w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                        :class="accountType === 'professional' ? 'border-primary-500 bg-primary-500' : 'border-gray-300 dark:border-gray-600'">
                                        <svg v-if="accountType === 'professional'" class="w-3 h-3 text-white" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                    Offer a service. No Business required.
                                </p>
                            </button>

                            <!-- Owner -->
                            <button type="button" @click="accountType = 'owner'; form.account_type = 'owner'"
                                class="relative p-4 rounded-2xl border-2 transition-all text-left" :class="accountType === 'owner'
                                    ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'">
                                <div class="flex items-center gap-2.5 mb-2">
                                    <span class="text-2xl">🏢</span>
                                    <span class="font-bold text-gray-900 dark:text-white text-sm tracking-tight">An organization</span>
                                    <div class="ml-auto w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                        :class="accountType === 'owner' ? 'border-amber-500 bg-amber-500' : 'border-gray-300 dark:border-gray-600'">
                                        <svg v-if="accountType === 'owner'" class="w-3 h-3 text-white" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                    A Business can group several Listings.
                                </p>
                            </button>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Name -->
                        <div>
                            <label for="name"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Full name *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <input id="name" type="text" v-model="form.name"
                                    class="w-full pl-11 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                    placeholder="John Doe" required autofocus autocomplete="name" />
                            </div>
                            <p v-if="form.errors.name"
                                class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Email address *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </div>
                                <input id="email" type="email" v-model="form.email"
                                    class="w-full pl-11 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                    placeholder="you@example.com" required autocomplete="username" />
                            </div>
                            <p v-if="form.errors.email"
                                class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Phone number <span class="text-gray-400 dark:text-gray-500 normal-case text-[10px]">(optional)</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <input id="phone" type="tel" v-model="form.phone"
                                    class="w-full pl-11 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                    placeholder="+237 699 123 456" />
                            </div>
                            <p v-if="form.errors.phone" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                form.errors.phone }}</p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Password *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input :type="showPassword ? 'text' : 'password'" id="password" v-model="form.password"
                                    class="w-full pl-11 pr-12 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                    placeholder="Create a strong password" required autocomplete="new-password" />
                                <button type="button" @click="togglePasswordVisibility"
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <svg v-if="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Requirements -->
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                <p class="text-xs flex items-center gap-1.5"
                                    :class="passwordRequirements.minLength ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'">
                                    <svg v-if="passwordRequirements.minLength" class="h-3.5 w-3.5" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span v-else
                                        class="w-3.5 h-3.5 border border-gray-300 dark:border-gray-600 rounded-full flex-shrink-0"></span>
                                    8+ characters
                                </p>
                                <p class="text-xs flex items-center gap-1.5"
                                    :class="passwordRequirements.hasUpperCase ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'">
                                    <svg v-if="passwordRequirements.hasUpperCase" class="h-3.5 w-3.5"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span v-else
                                        class="w-3.5 h-3.5 border border-gray-300 dark:border-gray-600 rounded-full flex-shrink-0"></span>
                                    Upper & lowercase
                                </p>
                                <p class="text-xs flex items-center gap-1.5"
                                    :class="passwordRequirements.hasNumber ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'">
                                    <svg v-if="passwordRequirements.hasNumber" class="h-3.5 w-3.5" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span v-else
                                        class="w-3.5 h-3.5 border border-gray-300 dark:border-gray-600 rounded-full flex-shrink-0"></span>
                                    A number
                                </p>
                                <p class="text-xs flex items-center gap-1.5"
                                    :class="passwordRequirements.hasSpecial ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'">
                                    <svg v-if="passwordRequirements.hasSpecial" class="h-3.5 w-3.5" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span v-else
                                        class="w-3.5 h-3.5 border border-gray-300 dark:border-gray-600 rounded-full flex-shrink-0"></span>
                                    A special character
                                </p>
                            </div>
                            <p v-if="form.errors.password" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                form.errors.password }}</p>
                        </div>

                        <!-- Confirm password -->
                        <div>
                            <label for="password_confirmation"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Confirm password *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input :type="showConfirmPassword ? 'text' : 'password'" id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    class="w-full pl-11 pr-12 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                    placeholder="Confirm your password" required autocomplete="new-password" />
                                <button type="button" @click="toggleConfirmPasswordVisibility"
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <svg v-if="!showConfirmPassword" class="h-4 w-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="form.errors.password_confirmation" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">
                                {{ form.errors.password_confirmation }}</p>
                        </div>

                        <!-- Terms -->
                        <div>
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" v-model="form.terms" id="terms"
                                    class="mt-0.5 h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 transition-colors" />
                                <span class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                    I agree to the
                                    <a href="#"
                                        class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">Terms
                                        of Service</a>
                                    and
                                    <a href="#"
                                        class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">Privacy
                                        Policy</a>
                                </span>
                            </label>
                            <p v-if="form.errors.terms" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                form.errors.terms }}</p>
                        </div>

                        <!-- Submit -->
                        <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 text-sm"
                            :disabled="form.processing">
                            <span v-if="form.processing" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Creating account…
                            </span>
                            <span v-else class="flex items-center gap-2">
                                {{ accountType === 'owner' ? 'Continue to Business Setup' : 'Create Account' }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </span>
                        </button>

                        <p class="text-center text-sm text-gray-500 dark:text-gray-400 pt-2">
                            Already have an account?
                            <a href="/login"
                                class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">
                                Sign In
                            </a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
    import { ref, computed, watch } from 'vue';
    import { Head, useForm } from '@inertiajs/vue3';
    import GuestLayout from '@/Layouts/GuestLayout.vue';

    const urlParams = new URLSearchParams(window.location.search);
    const urlIntent = urlParams.get('intent') || null;

    const accountType = ref(urlIntent === 'business' ? 'owner' : 'user');

    const form = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        terms: false,
        account_type: accountType.value,
    });

    const isBusinessIntent = computed(() => accountType.value === 'owner');

    const showPassword = ref(false);
    const showConfirmPassword = ref(false);

    const togglePasswordVisibility = () => {
        showPassword.value = !showPassword.value;
    };

    const toggleConfirmPasswordVisibility = () => {
        showConfirmPassword.value = !showConfirmPassword.value;
    };

    const passwordRequirements = computed(() => {
        const password = form.password || '';
        return {
            minLength: password.length >= 8,
            hasUpperCase: /[A-Z]/.test(password) && /[a-z]/.test(password),
            hasNumber: /\d/.test(password),
            hasSpecial: /[!@#$%^&*(),.?":{}|<>]/.test(password),
        };
    });

    const submit = () => {
        form.post('/register', {
            onSuccess: () => {
                // Redirect handled by backend
            },
        });
    };

    watch(accountType, (val) => {
        form.account_type = val;
    });
</script>