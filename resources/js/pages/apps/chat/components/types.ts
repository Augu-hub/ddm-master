export type ContactType = {
    image?: string;
    icon?: string;
    name: string;
    lastMessage?: string;
    timestamp: string;
    isTyping?: boolean;
    isActive?: boolean;
    isPinned?: boolean;
    seen?: boolean;
    sent?: boolean;
    unreadMessages?: number;
    url: string;
};

export type ChatMessageType = {
    messages: string[];
    timestamp: string;
    sender?: {
        image: string;
        name: string;
    };
    recipient?: {
        image: string;
        name: string;
    };
};
