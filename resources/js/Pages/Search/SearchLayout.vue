<script setup lang="ts">

import BaseLayout from "@/Layouts/BaseLayout.vue";
import {Head, router} from "@inertiajs/vue3";
import {PagedOffers} from "@/types/Offers";
import {computed, PropType, ref, watch} from "vue";

const { offers, search, loadingSearch, time } = defineProps({
    offers: Object as PropType<PagedOffers>,
    title: String,
    search: String,
    loadingSearch: Boolean,
    time: Number
});
const pages = computed(() => offers ? Array.from(Array(offers.last_page).keys()).map((x) => x+1) : []);
const selectedPage = ref(offers?.current_page);
const loadingPage = computed(() => selectedPage.value !== offers?.current_page || loadingSearch);
watch(() => search, () => {
    selectedPage.value = 1;
})
const goToPage = (page: number) => {
    router.get(window.location.href, { search: search, page }, { preserveState: true });
    selectedPage.value = page;
    window.scrollTo(0, 0);
}
const goToSelectedPage = (offset: number = 0) => {
    goToPage(Number(selectedPage.value) + offset);
}
</script>

<template>
    <Head :title="title" />

    <BaseLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                {{title}}
            </h2>
        </template>

        <div class="py-12">
            <div class=" mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="transition duration-500"
                    :style="{'padding-top': search ? '0px' : 'calc(40vh - 150px)', 'transition-property': 'padding'}"
                >
                    <div v-if="!search" class="w-full text-center text-gray-900 dark:text-gray-100 text-3xl pb-5">
                        What are you looking for?
                    </div>
                    <slot />
                </div>
                <div v-if="loadingPage" class="grid justify-center text-5xl">
                    <v-icon icon="mdi-loading" size="x-large" color="white" class="animate-spin"></v-icon>
                </div>
                <div
                    v-if="offers && !loadingPage"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800 my-5"
                    v-for="offer in offers?.data"
                >
                    <div
                        class="grid items-center p-6 text-gray-900 dark:text-gray-100"
                        style="grid-template-columns: min-content auto"
                    >
                        <div
                            class="w-fit h-fit whitespace-nowrap p-1 mr-3 border-2 rounded-lg dark:border-gray-200 border-grey-800"
                        >
                            {{offer.category}}
                        </div>
                        <div class="h-fit">
                            {{offer.title}}
                        </div>
                    </div>
                    <div v-if="offer.description" class="p-3 text-gray-900 dark:text-gray-100 whitespace-pre-line">
                        {{offer.description.replaceAll('\\n', '\n')}}
                    </div>
                    <div v-if="offer.features" class="p-3 text-gray-900 dark:text-gray-100 whitespace-pre-line">
                        Features:
                        <ul class="list-disc list-inside">
                            <li v-for="feature in offer.features.split('\\n')">
                                {{feature}}
                            </li>
                        </ul>
                    </div>
                    <div v-if="offer.details" class="p-3 text-gray-900 dark:text-gray-100 whitespace-pre-line">
                        <div
                            v-for="detail in offer.details.split('\n')"
                            class="inline-block border p-1 mx-1 rounded-lg dark:border-gray-400 border-grey-600"
                        >
                            {{detail}}
                        </div>
                    </div>
                </div>
                <div v-if="search && !offers?.data.length" class="w-full text-red-900 dark:text-red-500 text-center">
                    Sorry, we didn't found any offers
                </div>
                <div class="text-white w-full grid justify-center" v-if="offers?.data.length">
                    <div class="w-full pb-2 text-gray-900 dark:text-gray-100 text-center">
                        Found offers: {{offers?.total}} in {{time}} seconds
                    </div>
                    <div class="grid align-center" style="grid-template-columns: repeat(5, min-content);">
                        <a class="text-3xl" v-if="(offers?.current_page ?? 0) > 2" @click="goToPage(1)">
                            <v-icon icon="mdi-chevron-double-left" color="white" size="small"></v-icon>
                        </a>
                        <a class="text-3xl" v-if="(offers?.current_page ?? 0) > 1" @click="goToSelectedPage(-1)">
                            <v-icon icon="mdi-chevron-left" color="white" size="small"></v-icon>
                        </a>
                        <select
                            class="text-gray-900 bg-gray-200 dark:text-gray-100 dark:bg-gray-800"
                            v-model="selectedPage"
                            :disabled="loadingPage"
                            @change="goToSelectedPage(0)"
                        >
                            <option v-for="page in pages" :selected="page === selectedPage">{{page}}</option>
                        </select>
                        <a
                            class="text-3xl"
                            v-if="(offers?.current_page ?? 0) < (offers?.last_page ?? 0) - 1"
                            @click="goToSelectedPage(1)"
                        >
                            <v-icon icon="mdi-chevron-right" color="white" size="small"></v-icon>
                        </a>
                        <a
                            class="text-3xl"
                            v-if="(offers?.current_page ?? 0) < (offers?.last_page ?? 0) - 2"
                            @click="goToPage(Number(offers?.last_page))"
                        >
                            <v-icon icon="mdi-chevron-double-right" color="white" size="small"></v-icon>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

<style scoped>

</style>
