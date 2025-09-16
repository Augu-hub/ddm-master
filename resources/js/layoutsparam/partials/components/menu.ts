import { menu } from '@/helpers/menuparam';

export const getActiveItem = (currentRoute: string) => {
    for (const i of menu) {
        if (i.children != undefined) {
            for (const j of i.children) {
                if (j.url === currentRoute) {
                    return j;
                } else if (j.children != undefined) {
                    for (const k of j.children) {
                        if (k.url === currentRoute) {
                            return k;
                        }
                    }
                }
            }
        }
    }
    return null;
};

export const getParentOfActiveItem = (parentKey: string) => {
    for (const i of menu) {
        if (i.key === parentKey) {
            return i;
        }

        if (i.children != undefined) {
            for (const j of i.children) {
                if (j.key === parentKey) {
                    return j;
                }
            }
        }
    }
    return null;
};
