interface Counter {
    create: (nodeId: string) => void;
    getValue: () => number;
    incValue: (by: number) => void;
    setValue: (value: number) => void;
    toValue: (value: number) => void;
    disable: () => void;
}