<template>
  <div class="creative-item" :class="`_${activeTab}`">
    <div
      class="creative-video"
      :class="{ 'has-video': creative.has_video }"
      @mouseenter="onVideoHover"
      @mouseleave="onVideoLeave"
    >
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
      <span v-if="creative.has_video" class="icon-play"></span>
      <div v-if="creative.duration" class="creative-video__time">{{ creative.duration }}</div>
      <div
        class="creative-video__content"
        :data-video="creative.video_url || 'img/video-3.mp4'"
        v-html="videoContent"
      ></div>
    </div>
    <div class="creative-item__row">
      <div class="creative-item__icon thumb"><img :src="icon" alt="" /></div>
      <div class="creative-item__title">{{ creative.title }}</div>
      <div class="creative-item__platform">
        <img :src="activeTab === 'facebook' ? facebookIcon : tiktokIcon" alt="" />
      </div>
    </div>
    <div class="creative-item__row">
      <div class="creative-item__desc font-roboto">
        {{ creative.description }}
      </div>
      <div class="creative-item__copy">
        <button class="btn-icon js-copy _border-gray">
          <span class="icon-copy"></span>
          <span class="icon-check d-none"></span>
        </button>
      </div>
    </div>
    <div class="creative-item__social">
      <div class="creative-item__social-item"><strong>285</strong> <span>Like</span></div>
      <div class="creative-item__social-item"><strong>2</strong> <span>Comments</span></div>
      <div class="creative-item__social-item"><strong>7</strong> <span>Shared</span></div>
    </div>
    <div class="creative-item__footer">
      <div class="creative-item__info">
        <div class="creative-status icon-dot font-roboto">
          Active: {{ creative.activity_date }} day
        </div>
      </div>
      <div class="creative-item__btns">
        <div class="creative-item-info"><img src="@img/flags/KZ.svg" alt="" /></div>
        <button class="btn-icon btn-favorite"><span class="icon-favorite-empty"></span></button>
        <button class="btn-icon _dark js-show-details"><span class="icon-info"></span></button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Creative, TabValue } from '@/types/creatives.d';
import facebookIcon from '@img/facebook.svg';
import icon from '@img/icon-1.jpg';
import tiktokIcon from '@img/tiktok.svg';
// import instagramIcon from '@img/instagram.svg';
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
  // Проверяем наличие флага has_video и состояние hover
  if (!creative.value.has_video || !isVideoHovered.value) {
    return '';
  }

  const videoUrl = creative.value.video_url || 'img/video-3.mp4';
  return `<video loop="loop" autoplay muted="muted" webkit-playsinline playsinline controls>
    <source type="video/mp4" src="${videoUrl}">
  </video>`;
});

// Обработчики событий hover - работают только при наличии видео
const onVideoHover = () => {
  if (creative.value.has_video) {
    isVideoHovered.value = true;
  }
};

const onVideoLeave = () => {
  if (creative.value.has_video) {
    isVideoHovered.value = false;
  }
};
</script>

<style scoped lang="scss">
// // Стили для социальных карточек креативов
.creative-video {
  &.has-video:hover {
    .creative-video__content {
      height: calc(100% + 165px);
      opacity: 1;
      visibility: visible;
    }
  }
}
</style>
