<template>
    <div class="list-group list-group-flush">
        <a
            v-for="log in logs"
            :key="log.key"
            class="list-group-item list-group-item-action log-item"
            href="#"
            @click.prevent="viewLog(log.links.self, log.tail)"
        >
            <span class="log-name">{{ log.name }}</span><br>
            <small class="text-secondary">{{ log.path }}</small>
        </a>
    </div>
</template>

<script setup lang="ts">
import {useAsyncState} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";
import {ApiLogType} from "~/entities/ApiInterfaces.ts";

const props = defineProps<{
    url: string
}>();

const emit = defineEmits<{
    (e: 'view', url: string, isStreaming: boolean): void
}>();

const {axios} = useAxios();

const {state: logs} = useAsyncState<ApiLogType[]>(
    async () => (await axios.get<ApiLogType[]>(props.url)).data,
    []
);

const viewLog = (url: string, isStreaming: boolean) => {
    emit('view', url, isStreaming);
};
</script>
