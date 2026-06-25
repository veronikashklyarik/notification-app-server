<x-layouts.app>
    <div class="stagger-children" x-data="{ isInstalled: false }" x-init="isInstalled = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true">

        <div class="px-5 pt-6 pb-2">
            <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Add to Home Screen</h1>
        </div>

        {{-- Already installed banner --}}
        <div class="px-4 mt-4" x-show="isInstalled" x-cloak>
            <div class="card p-5 border-emerald-100 bg-emerald-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-emerald-800">App already installed</p>
                        <p class="text-xs text-emerald-600 mt-0.5">You're running the app from your home screen.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Intro card --}}
        <div class="px-4 mt-4" x-show="!isInstalled">
            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <img src="/apple-touch-icon.png" alt="App icon" class="w-16 h-16 rounded-2xl shadow-md shrink-0">
                    <div>
                        <p class="text-base font-bold text-gray-900">Notifyr</p>
                        <p class="text-sm text-gray-400 mt-0.5">Install for the best experience — works offline, no browser UI.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- iOS Instructions --}}
        <div class="px-4 mt-5" x-show="!isInstalled">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3 px-1">iPhone / iPad (Safari)</p>
            <div class="card p-5 space-y-4">
                {{-- Step 1 --}}
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-xs font-bold text-indigo-700">1</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">Open in Safari</p>
                        <p class="text-xs text-gray-400 mt-0.5">This page must be open in Safari — Chrome and Firefox on iOS don't support installation.</p>
                    </div>
                </div>
                <div class="border-t border-gray-50"></div>
                {{-- Step 2 --}}
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-xs font-bold text-indigo-700">2</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">Tap the Share button</p>
                        <p class="text-xs text-gray-400 mt-0.5">The Share button is the square with an arrow pointing up — it's in the middle of the bottom toolbar.</p>
                        <div class="mt-2 flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-xl border border-gray-100 w-fit">
                            <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path id="Vector" d="M9 6L12 3M12 3L15 6M12 3V13M7.00023 10C6.06835 10 5.60241 10 5.23486 10.1522C4.74481 10.3552 4.35523 10.7448 4.15224 11.2349C4 11.6024 4 12.0681 4 13V17.8C4 18.9201 4 19.4798 4.21799 19.9076C4.40973 20.2839 4.71547 20.5905 5.0918 20.7822C5.5192 21 6.07899 21 7.19691 21H16.8036C17.9215 21 18.4805 21 18.9079 20.7822C19.2842 20.5905 19.5905 20.2839 19.7822 19.9076C20 19.4802 20 18.921 20 17.8031V13C20 12.0681 19.9999 11.6024 19.8477 11.2349C19.6447 10.7448 19.2554 10.3552 18.7654 10.1522C18.3978 10 17.9319 10 17 10" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="text-xs font-semibold text-gray-700">Share</span>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-50"></div>
                {{-- Step 3 --}}
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-xs font-bold text-indigo-700">3</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">Tap "Add to Home Screen"</p>
                        <p class="text-xs text-gray-400 mt-0.5">Scroll down in the share sheet to find this option, then tap <strong class="text-gray-600">Add</strong> in the top right.</p>
                        <div class="mt-2 flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-xl border border-gray-100 w-fit">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="text-xs font-semibold text-gray-700">Add to Home Screen</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Android Instructions --}}
        <div class="px-4 mt-5" x-show="!isInstalled">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3 px-1">Android (Chrome)</p>
            <div class="card p-5 space-y-4">
                {{-- Step 1 --}}
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-full bg-violet-100 flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-xs font-bold text-violet-700">1</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">Open in Chrome</p>
                        <p class="text-xs text-gray-400 mt-0.5">Make sure you're using Chrome — it has the best PWA install support on Android.</p>
                    </div>
                </div>
                <div class="border-t border-gray-50"></div>
                {{-- Step 2 --}}
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-full bg-violet-100 flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-xs font-bold text-violet-700">2</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">Tap the three-dot menu</p>
                        <p class="text-xs text-gray-400 mt-0.5">The menu is in the top-right corner of Chrome.</p>
                        <div class="mt-2 inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gray-100">
                            <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-50"></div>
                {{-- Step 3 --}}
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-full bg-violet-100 flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-xs font-bold text-violet-700">3</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">Tap "Add to Home Screen"</p>
                        <p class="text-xs text-gray-400 mt-0.5">You may see "Install App" instead — both options work. Confirm in the dialog that appears.</p>
                        <div class="mt-2 flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-xl border border-gray-100 w-fit">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span class="text-xs font-semibold text-gray-700">Add to Home Screen</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="h-8"></div>
    </div>
</x-layouts.app>
