<script setup lang="ts">

import {PagedOffers} from "@/types/Offers";
import {PropType, Ref, ref, watch} from "vue";
import SearchLayout from "@/Pages/Search/SearchLayout.vue";
import {router} from "@inertiajs/vue3";

const { search, offers, time } = defineProps({ offers: Object as PropType<PagedOffers>, search: String, time: Number })

const currentSearch = ref(search);
const loadingSearch = ref(false);
const error: Ref<string|null> = ref(null);
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
    router.get('/offers', { search: currentSearch.value }, { preserveState: true })
}
</script>

<template>
   <SearchLayout :offers="offers" title="Basic Offers Search" :search="search" :loadingSearch="loadingSearch" :time="time">
       <form
           method="get"
           class="grid items-center p-2 text-gray-900 dark:text-gray-100"
           style="grid-template-columns: auto max-content"
           @submit.prevent="submit"
       >
           <textarea name="search" v-model="currentSearch" @keydown.enter="submit" class="w-full dark:bg-gray-800"/>
           <button
               type="submit"
               class="p-2 mx-3 border sm:rounded-lg shadow-md hover:bg-gray-500
                            bg-gray-100 shadow-gray-600 active:bg-gray-100
                            dark:bg-gray-800 dark:shadow-gray-300 active:dark:bg-gray-800"
           >Search</button>
       </form>
       <div v-if="error" class="w-full text-red-900 dark:text-red-500 text-center">
           {{error}}
       </div>
   </SearchLayout>
</template>

<style scoped>

</style>
