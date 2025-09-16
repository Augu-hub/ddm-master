<template>
    <b-form
        ref="formRef"
        action="/"
        method="post"
        class="dropzone"
        data-plugin="dropzone"
        data-previews-container="#file-previews"
        data-upload-preview-template="#uploadPreviewTemplate"
    >
        <div class="fallback">
            <input type="file" name="file" :multiple="multiple" @change="onInputChange" v-bind="$attrs" />
        </div>
        <div class="dz-message needsclick">
            <i class="h1 ti ti-cloud-upload mb-4"></i>
            <h4>Drop files here or click to upload.</h4>
            <span class="text-muted fs-13">(This is just a demo dropzone. Selected files are <strong>not</strong> actually uploaded.)</span>
        </div>
    </b-form>

    <ul class="list-unstyled mb-0" id="dropzone-preview">
        <li class="card mb-0 mt-1 border shadow-none" id="dropzone-preview-list">
            <div class="p-2">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <img data-dz-thumbnail src="#" class="avatar-sm bg-light rounded" alt="" />
                    </div>
                    <div class="col ps-0">
                        <a href="javascript:void(0);" class="text-muted fw-bold" data-dz-name></a>
                        <p class="mb-0" data-dz-size></p>
                    </div>
                    <div class="col-auto">
                        <a href="" class="btn btn-link btn-lg text-muted" data-dz-remove>
                            <i class="ti ti-x"></i>
                        </a>
                    </div>
                </div>
            </div>
        </li>
    </ul>
</template>

<script setup lang="ts">
import useDropzoneUploader from '@/hooks/useDropzoneUploader';
import { nextTick, onMounted, ref } from 'vue';

const props = defineProps<{ modelValue?: File[] | null; multiple?: boolean }>();
const emit = defineEmits(['update:modelValue']);

const formRef = ref<HTMLFormElement | null>(null);
const { onInputChange, initDropzone } = useDropzoneUploader(props, emit);

onMounted(() => {
    nextTick(() => {
        initDropzone();
    });
});
</script>
