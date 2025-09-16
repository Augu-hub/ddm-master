export const chunkArray = (array: any[], size: number) => {
    const result = [];

    for (let i = 0; i < array.length; i += size) {
        const chunk = array.slice(i, i + size);
        result.push(chunk);
    }

    return result;
};

export const stringOrNumberToArray = (input: string | number) => {
    const result = [];
    if (typeof input === 'number') {
        input = input.toString();
    }
    for (let i = 0; i < input.length; i++) {
        result.push(input[i]);
    }
    return result;
};
