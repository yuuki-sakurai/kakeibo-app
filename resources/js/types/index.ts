export type Page = "home" | "transactions" | "imports" | "cards";

export interface NavItem {
    key: Page;
    label: string;
    icon: string;
}

export interface Transaction {
    date: string;
    merchant: string;
    category: string;
    amount: number;
    paymentMonth: string;
}

export interface ImportHistory {
    fileName: string;
    importedAt: string;
    importedCount: number;
    excludedCount: number;
    hasWarning: boolean;
}
