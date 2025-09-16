export type EmailType = {
    isStared?: boolean;
    sender: {
        image: string;
        name: string;
    };
    subject: string;
    message: string;
    attachmentCount?: number;
    receivedAt: string;
    label?: 'personal' | 'client' | 'marketing' | 'office';
};
