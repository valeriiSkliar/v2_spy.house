// Main type declarations for the application

/// <reference types="vite/client" />

// Vue components type declarations
declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<{}, {}, any>;
    export default component;
}