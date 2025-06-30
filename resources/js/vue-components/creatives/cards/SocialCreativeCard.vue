<template>
  <div class="creative-item" :class="`_${activeTab}`">
    <div class="creative-video" @mouseenter="onVideoHover" @mouseleave="onVideoLeave">
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
      <div
        class="creative-video__content"
        :data-video="creative.video_url || 'img/video-3.mp4'"
        v-html="videoContent"
      ></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Creative, TabValue } from '@/types/creatives.d';
import { computed, ref } from 'vue';

const props = defineProps<{
  activeTab: TabValue;
  creative: Creative;
}>();

const activeTab = computed(() => props.activeTab);
const creative = computed(() => props.creative);

// Реактивное состояние для отслеживания hover
const isVideoHovered = ref(false);

// Computed для управления контентом видео
const videoContent = computed(() => {
  if (!isVideoHovered.value) {
    return '';
  }

  const videoUrl = creative.value.video_url || 'img/video-3.mp4';
  return `<video loop="loop" autoplay muted="muted" webkit-playsinline playsinline controls>
    <source type="video/mp4" src="${videoUrl}">
  </video>`;
});

// Обработчики событий hover
const onVideoHover = () => {
  isVideoHovered.value = true;
};

const onVideoLeave = () => {
  isVideoHovered.value = false;
};
</script>
