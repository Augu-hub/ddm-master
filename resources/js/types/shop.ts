export type IdType = number;

export type ProductType = {
    id: IdType;
    image: string;
    tag?: string;
    category: string;
    name: string;
    size: string[];
    rating: number;
    totalReview: number;
    price: number;
    discount?: {
        type: 'amount' | 'percent';
        amount: number;
    };
    url: string;
};

export type CartType = {
    id: IdType;
    productId: IdType;
    product?: ProductType;
    quantity: number;
};

export type WishlistType = ProductType;

export type CalculatedOrder = {
    total: number;
    totalDiscount: number;
    tax: number;
    orderTotal: number;
};

export type ShoppingState = {
    cartItems: CartType[];
    wishlists: WishlistType[];
    toggleToWishlist: (product: ProductType) => void;
    isInWishlist: (product: ProductType) => boolean;
    isInCart: (product: ProductType) => boolean;
    updateQuantity: (product: ProductType, quantity: number) => void;
    getCalculatedOrder: () => CalculatedOrder;
    getCartItemById: (productId: ProductType) => CartType | undefined;
};
