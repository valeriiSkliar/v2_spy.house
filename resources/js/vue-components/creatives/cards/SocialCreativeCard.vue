<template>
  <div class="creative-item" :class="`_${activeTab}`">
    <div class="creative-video">
      <div class="thumb">
        <img
          src="https://dev.vitaliimaksymchuk.com.ua/spy/img/facebook-2.jpg"
          alt=""
          class="thumb-blur"
        />
        <!--TODO: change to real image-->
        <img
          src="https://dev.vitaliimaksymchuk.com.ua/spy/img/facebook-2.jpg"
          alt=""
          class="thumb-contain"
        />
        <!--TODO: change to real image-->
      </div>
      <span class="icon-play"></span>
      <div class="creative-video__time">{{ creative.duration }}</div>
      <div class="creative-video__content" data-video="img/video-3.mp4"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Creative, TabValue } from '@/types/creatives.d';
import { computed } from 'vue';

const props = defineProps<{
  activeTab: TabValue;
  creative: Creative;
}>();

const activeTab = computed(() => props.activeTab);
const creative = computed(() => props.creative);
</script>
