import Dropzone from 'dropzone';

export default function useDropzoneUploader(
    props: { modelValue?: File[] | null; multiple?: boolean },
    emit: (event: 'update:modelValue', payload: File[]) => void,
) {
    let dropzoneInstance: Dropzone | null = null;

    const onInputChange = (e: Event) => {
        const files = (e.target as HTMLInputElement).files;
        if (files) {
            emit('update:modelValue', Array.from(files));
        }
    };

    const updateFileList = () => {
        if (dropzoneInstance) {
            emit('update:modelValue', dropzoneInstance.files);
        }
    };

    const initDropzone = (formEl?: HTMLFormElement | null) => {
        const el = formEl ?? (document.querySelector('.dropzone') as HTMLFormElement | null);

        if (!el || !(el instanceof HTMLFormElement)) {
            console.error('Dropzone element not found or not an HTMLFormElement');
            return;
        }

        const previewNode = document.querySelector('#dropzone-preview-list') as HTMLElement;
        if (!previewNode) {
            console.warn('Preview node not found');
            return;
        }

        const previewTemplate = previewNode.parentElement?.innerHTML ?? '';
        previewNode.remove();

        dropzoneInstance = new Dropzone(el, {
            url: 'https://httpbin.org/post',
            method: 'post',
            autoProcessQueue: false,
            previewsContainer: '#dropzone-preview',
            previewTemplate,
        });

        dropzoneInstance.on('addedfile', updateFileList);
        dropzoneInstance.on('removedfile', updateFileList);
    };

    return {
        onInputChange,
        initDropzone,
    };
}
