<script setup lang="ts">
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav
                class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('offers')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('offers')"
                                    :active="route().current('offers')"
                                >
                                    Default
                                </NavLink>
                                <NavLink
                                    :href="route('offers.remove_word')"
                                    :active="route().current('offers.remove_word')"
                                >
                                    Remove word
                                </NavLink>
                                <NavLink
                                    :href="route('offers.chatbot_rephrase')"
                                    :active="route().current('offers.chatbot_rephrase')"
                                >
                                    Rephrase query
                                </NavLink>
                                <NavLink
                                    :href="route('offers.chatbot_add')"
                                    :active="route().current('offers.chatbot_add')"
                                >
                                    Add words (chatbot)
                                </NavLink>
                                <NavLink
                                    :href="route('offers.chatbot_remove')"
                                    :active="route().current('offers.chatbot_remove')"
                                >
                                    Remove words (chatbot)
                                </NavLink>
                                <NavLink
                                    :href="route('offers.words_to_base')"
                                    :active="route().current('offers.words_to_base')"
                                >
                                    Bring words to base form
                                </NavLink>
                                <NavLink
                                    :href="route('offers.bm25rm3')"
                                    :active="route().current('offers.bm25rm')"
                                >
                                    BM25 + RM3
                                </NavLink>
                                <NavLink
                                    :href="route('offers.flanqr')"
                                    :active="route().current('offers.flanqr')"
                                >
                                    FlanQR
                                </NavLink>
                                <NavLink
                                    :href="route('offers.flanprf')"
                                    :active="route().current('offers.flanprf')"
                                >
                                    FlanPRF
                                </NavLink>
                                <NavLink
                                    :href="route('offers.snippets')"
                                    :active="route().current('offers.snippets')"
                                >
                                    Snippets
                                </NavLink>
                                <NavLink
                                    :href="route('offers.ngrams')"
                                    :active="route().current('offers.ngrams')"
                                >
                                    Ngrams
                                </NavLink>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-900 dark:hover:text-gray-400 dark:focus:bg-gray-900 dark:focus:text-gray-400"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Dashboard
                        </ResponsiveNavLink>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white shadow dark:bg-gray-800"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
