import { toggleDocumentAttribute } from '@/helpers/other';
import { useLocalStorage } from '@vueuse/core';
import { defineStore } from 'pinia';

type LayoutType = {
    theme: 'light' | 'dark';
    layoutMode: 'fluid' | 'detached';
    topBarColor: 'light' | 'dark' | 'brand';
    leftSideBarColor: 'light' | 'dark' | 'brand';
    leftSideBarSize: 'full' | 'fullscreen' | 'sm-hover' | 'compact' | 'condensed' | 'default' | 'sm-hover-active';
};

const defaultConfig: LayoutType = {
    theme: 'light',
    layoutMode: 'fluid',
    topBarColor: 'light',
    leftSideBarColor: 'dark',
    leftSideBarSize: 'sm-hover-active',
};

export const useLayoutStore = defineStore('layout_store', () => {
    const layout = useLocalStorage<LayoutType>('__osen_layout_session__', defaultConfig);

    const setTheme = (nTheme: LayoutType['theme']) => {
        layout.value.theme = nTheme;
        toggleDocumentAttribute('data-bs-theme', layout.value.theme);
    };

    const setLayoutMode = (nMode: LayoutType['layoutMode']) => {
        layout.value.layoutMode = nMode;
        toggleDocumentAttribute('data-layout-mode', layout.value.layoutMode);
    };

    const setTopBarColor = (nColor: LayoutType['topBarColor']) => {
        layout.value.topBarColor = nColor;
        toggleDocumentAttribute('data-topbar-color', layout.value.topBarColor);
    };

    const setLeftSideBarColor = (nColor: LayoutType['leftSideBarColor']) => {
        layout.value.leftSideBarColor = nColor;
        toggleDocumentAttribute('data-menu-color', layout.value.leftSideBarColor);
    };

    const setLeftSideBarSize = (nSize: LayoutType['leftSideBarSize']) => {
        layout.value.leftSideBarSize = nSize;
        toggleDocumentAttribute('data-sidenav-size', layout.value.leftSideBarSize);
    };

    const reset = () => {
        setTheme(defaultConfig.theme);
        setLayoutMode(defaultConfig.layoutMode);
        setTopBarColor(defaultConfig.topBarColor);
        setLeftSideBarColor(defaultConfig.leftSideBarColor);
        setLeftSideBarSize(defaultConfig.leftSideBarSize);
    };

    const init = () => {
        setTheme(layout.value.theme);
        setLayoutMode(layout.value.layoutMode);
        setTopBarColor(layout.value.topBarColor);
        setLeftSideBarColor(layout.value.leftSideBarColor);
        setLeftSideBarSize(layout.value.leftSideBarSize);
    };

    return {
        layout,
        setTheme,
        setLayoutMode,
        setTopBarColor,
        setLeftSideBarColor,
        setLeftSideBarSize,
        reset,
        init,
    };
});
