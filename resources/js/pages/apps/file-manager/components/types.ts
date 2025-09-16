export type AppType = {
    icon: string;
    name: string;
    isStared?: boolean;
    totalFiles: number;
    usedSpace: number;
    totalSpace: number;
    variant: string;
};

export type FileActivityType = {
    file: {
        name: string;
        totalFiles?: number;
        type?: 'docx' | 'pdf' | 'xls' | 'txt' | 'jpg';
        icon: string;
        variant: string;
    };
    uploadedBy: {
        name: string;
        image: string;
        email: string;
    };
    size: string;
    lastUpdated: string;
    members: {
        name: string;
        variant: string;
    }[];
};
