<script setup lang="ts">

import {PagedOffers} from "@/types/Offers";
import {PropType, Ref, ref, watch} from "vue";
import SearchLayout from "@/Pages/Search/SearchLayout.vue";
import {router} from "@inertiajs/vue3";

const { search, offers, suggestions, time, title, score } = defineProps({
    offers: Object as PropType<PagedOffers>,
    search: String,
    suggestions: Array<string>,
    time: Number,
    title: String,
    score: Number,
})

const currentSearch = ref(search);
const loadingSearch = ref(false);
const error: Ref<string|null> = ref(null);
const searchFocus: Ref<boolean> = ref(false);
watch(() => search, () => loadingSearch.value = false)

const submit = (event: KeyboardEvent | Event) => {
    event.preventDefault();
    if (currentSearch.value === search) {
        return;
    }
    if (currentSearch.value?.length === 0) {
        error.value = 'Search cannot be empty.';
        return;
    }
    loadingSearch.value = true;
    router.get(window.location.href, { search: currentSearch.value, page: undefined }, { preserveState: true });
}

const updateSearch = (value: string) => {
    loadingSearch.value = true;
    currentSearch.value = value;
    router.get(window.location.href, { search: value, page: undefined }, { preserveState: true });
}
</script>

<template>
   <SearchLayout :offers="offers" :title="title" :search="search" :loadingSearch="loadingSearch" :time="time" :score="score">
       <form
           method="get"
           class="grid items-center p-2 text-gray-900 dark:text-gray-100"
           style="grid-template-columns: auto max-content"
           @submit.prevent="submit"
       >
           <textarea name="search" v-model="currentSearch" @focus="searchFocus = true" @blur="searchFocus = false" @keydown.enter="submit" class="w-full dark:bg-gray-800"/>
           <button
               type="submit"
               class="p-2 mx-3 border sm:rounded-lg shadow-md hover:bg-gray-500
                            bg-gray-100 shadow-gray-600 active:bg-gray-100
                            dark:bg-gray-800 dark:shadow-gray-300 active:dark:bg-gray-800"
           >Search</button>
       </form>
       <div
           v-if="suggestions && suggestions.length"
           class="w-full transition-[max-height] transition-padding duration-500 delay-200 grid items-center text-center content-center grid-cols-1 pr-2 pl-2 text-gray-900 dark:text-gray-100 overflow-hidden"
           :style="{ 'max-height': searchFocus ? '500px' : '0px' }"
       >
           <span v-if="suggestions.length === 1">Try searching for:</span>
           <span v-else>Choose new search phrase:</span>
           <button
               class="p-2 mx-2 border sm:rounded-lg shadow-sm hover:bg-gray-500
                            bg-gray-100 shadow-gray-600 active:bg-gray-100
                            dark:bg-gray-800 dark:shadow-gray-300 active:dark:bg-gray-800"
               v-for="suggestion in suggestions"
               type="button"
               v-on:click="updateSearch(suggestion)"
           >{{suggestion}}</button>
       </div>
       <div v-if="error" class="w-full text-red-900 dark:text-red-500 text-center">
           {{error}}
       </div>
   </SearchLayout>
</template>

<style scoped>

</style>
