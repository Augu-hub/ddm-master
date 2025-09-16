import type { ProductType } from '@/types/shop';

export const calculateDiscount = (product: ProductType): number => {
    return product.discount?.type == 'amount'
        ? product.discount.amount
        : product.discount?.type == 'percent'
          ? (product.price / 100) * product.discount.amount
          : 0;
};

export const getPreciseCurrency = (price: number) => {
    return parseFloat(price.toFixed(2));
};
