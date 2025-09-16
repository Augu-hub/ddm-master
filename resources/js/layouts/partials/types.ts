export type AppType = {
    image: string;
    name: string;
    username?: string;
};

export type NotificationType = {
    sender?: {
        image: string;
        name: string;
    };
    message: string;
    timestamp: string;
    type: 'commented' | 'donated' | 'followed' | 'liked' | 'other';
};
